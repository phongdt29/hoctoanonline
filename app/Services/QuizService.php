<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Lesson;
use App\Models\PointLedger;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Ticket L2 — quiz cuoi buoi. TIMER & ANTI-CHEAT QUYET DINH O SERVER (CLAUDE.md #7).
 *
 * start(): sinh cau hoi qua AI, luu snapshot (kem dap an dung) O SERVER, dat
 *   expires_at = now + duration. Client chi nhan cau hoi KHONG kem dap an.
 * submit(): server so dap an hoc sinh voi snapshot -> tu cham. Kiem tra
 *   now > expires_at. Sua gio client khong an thua (dung gio server).
 */
class QuizService
{
    public const SCHEMA = [
        'type' => 'object',
        'required' => ['questions'],
        'properties' => [
            'questions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'required' => ['topic', 'content', 'options', 'correct'],
                    'properties' => [
                        'topic' => ['type' => 'string'],
                        'content' => ['type' => 'string'],
                        'options' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'correct' => ['type' => 'string'],   // chu cai A/B/C/D
                    ],
                ],
            ],
        ],
    ];

    public function __construct(
        private readonly AiProviderService $ai,
        private readonly PointLedgerService $points,
        private readonly GamificationService $gamification,
    ) {}

    /** Bat dau: tra attempt dang mo neu con han, khong thi tao moi + sinh cau hoi. */
    public function start(Quiz $quiz, Student $student): QuizAttempt
    {
        // latest('started_at') chu khong phai latest() — QuizAttempt khong co created_at
        // (timestamps=false), latest() mac dinh order theo created_at se loi cot.
        $open = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->where('expires_at', '>', now())
            ->latest('started_at')
            ->first();

        if ($open) {
            return $open;
        }

        $snapshot = $this->generateQuestions($quiz, $student);

        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes($quiz->duration_minutes),
            'questions_snapshot' => $snapshot,
        ]);
    }

    /**
     * Nop bai. $answers keyed by question index -> chu cai hoc sinh chon.
     * Server tu cham bang snapshot — KHONG tin client noi cau nao dung.
     *
     * @param array<int, string> $answers
     */
    public function submit(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        if ($attempt->isSubmitted()) {
            return $attempt;   // idempotent — khong cham lai, khong double diem
        }

        $expired = $attempt->hasExpired();
        $snapshot = $attempt->questions_snapshot ?? [];

        return DB::transaction(function () use ($attempt, $answers, $snapshot, $expired) {
            $total = max(count($snapshot), 1);
            $correct = 0;
            $byTopic = [];

            foreach ($snapshot as $i => $question) {
                $topic = $question['topic'] ?? 'unknown';
                $byTopic[$topic] ??= ['total' => 0, 'wrong' => 0];
                $byTopic[$topic]['total']++;

                // Het gio: cau tra loi sau expires_at khong duoc tinh dung
                // (server quyet dinh, khong tin client).
                $studentAnswer = $expired ? null : ($answers[$i] ?? null);
                $isCorrect = $studentAnswer !== null
                    && strcasecmp((string) $studentAnswer, (string) ($question['correct'] ?? '')) === 0;

                if ($isCorrect) {
                    $correct++;
                } else {
                    $byTopic[$topic]['wrong']++;
                }
            }

            $score = round($correct / $total * 10, 2);
            $reviewMin = config('hoctoan.quiz.review_min');

            $attempt->update([
                'score' => $score,
                'error_analysis' => [
                    'by_topic' => $byTopic,
                    'expired' => $expired,
                    'correct' => $correct,
                    'total' => $total,
                ],
                'suggestion' => $score >= $reviewMin
                    ? QuizAttempt::SUGGESTION_HOC_TIEP
                    : QuizAttempt::SUGGESTION_ON_LAI,
                'submitted_at' => now(),
            ]);

            $this->points->record(
                $attempt->student,
                (int) round($score * 2),
                PointLedger::REASON_QUIZ_SCORE,
                $attempt->id,   // idempotent theo attempt -> khong double diem
            );

            if ($score >= config('hoctoan.quiz.new_lesson_min')) {
                $this->unlockNextLesson($attempt->quiz);
            }

            // Ticket R2 — cap nhat streak + trao huy hieu moi (idempotent).
            $this->gamification->touchStreak($attempt->student);
            $this->gamification->checkBadges($attempt->student->fresh());

            return $attempt->fresh();
        });
    }

    private function generateQuestions(Quiz $quiz, Student $student): array
    {
        $lesson = $quiz->lesson;
        $topic = $lesson->module?->topic ?? 'toan';

        $prompt = <<<PROMPT
        Tạo 5 câu trắc nghiệm kiểm tra cuối buổi cho bài học "{$lesson->title}" (chủ đề {$topic}),
        học sinh lớp {$student->grade}. Mỗi câu 4 lựa chọn. correct = chữ cái đáp án đúng (A/B/C/D).
        Trả về JSON đúng schema.
        PROMPT;

        $result = $this->ai->chat(AiLog::FEATURE_CURRICULUM, $prompt, self::SCHEMA, $student->id);

        return $result['questions'];
    }

    private function unlockNextLesson(Quiz $quiz): void
    {
        $lesson = $quiz->lesson;
        $lesson->update(['status' => Lesson::STATUS_COMPLETED]);

        $curriculum = $lesson->module?->curriculum;

        if (! $curriculum) {
            return;
        }

        $next = Lesson::query()
            ->whereIn('module_id', $curriculum->modules()->pluck('id'))
            ->where('status', Lesson::STATUS_LOCKED)
            ->orderBy('module_id')
            ->orderBy('lesson_order')
            ->first();

        $next?->update(['status' => Lesson::STATUS_UNLOCKED]);
    }
}
