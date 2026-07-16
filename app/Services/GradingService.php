<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Support\Facades\DB;

/**
 * Ticket C4 — cham bai danh gia.
 *
 * Trac nghiem: cham bang RULE (so khop dap an) — khong ton AI, khong sai.
 * Tu luan: cham qua AI (0..1 moi cau) — vi can hieu ban chat cau tra loi.
 *
 * Ket qua ghi vao is_correct tung cau + score tong (thang 10) cua assessment.
 */
class GradingService
{
    public const ESSAY_SCHEMA = [
        'type' => 'object',
        'required' => ['score'],
        'properties' => [
            'score' => ['type' => 'number'],   // 0..1
        ],
    ];

    public function __construct(private readonly AiProviderService $ai) {}

    public function grade(Assessment $assessment): void
    {
        $assessment->loadMissing('questions');

        DB::transaction(function () use ($assessment) {
            $totalPoints = 0.0;

            foreach ($assessment->questions as $question) {
                $points = $question->type === 'multiple_choice'
                    ? $this->gradeMultipleChoice($question)
                    : $this->gradeEssay($question, $assessment->student_id);

                $question->update(['is_correct' => $points >= 0.5]);
                $totalPoints += $points;
            }

            $count = max($assessment->questions->count(), 1);
            $score = round($totalPoints / $count * 10, 2);   // thang 10

            $assessment->update([
                'status' => Assessment::STATUS_GRADED,
                'score' => $score,
            ]);
        });
    }

    /** Trac nghiem: 1.0 neu khop, 0.0 neu sai. So sanh khong phan biet hoa thuong. */
    private function gradeMultipleChoice(AssessmentQuestion $question): float
    {
        $correct = trim((string) ($question->correct_answer['value'] ?? ''));
        $answer = trim((string) ($question->student_answer['value'] ?? ''));

        return $answer !== '' && strcasecmp($correct, $answer) === 0 ? 1.0 : 0.0;
    }

    /** Tu luan: AI cham 0..1. Neu hoc sinh bo trong -> 0, khong ton call AI. */
    private function gradeEssay(AssessmentQuestion $question, int $studentId): float
    {
        $answer = trim((string) ($question->student_answer['value'] ?? ''));

        if ($answer === '') {
            return 0.0;
        }

        $prompt = <<<PROMPT
        Chấm câu trả lời tự luận môn toán theo thang 0 đến 1.

        Đề bài: {$question->content}
        Đáp án chuẩn: {$question->correct_answer['value']}
        Bài làm của học sinh: {$answer}

        Trả về JSON {"score": <số thực 0..1>}. 1 = đúng hoàn toàn, 0 = sai hoàn toàn.
        PROMPT;

        $result = $this->ai->chat(AiLog::FEATURE_GRADING, $prompt, self::ESSAY_SCHEMA, $studentId);

        return max(0.0, min(1.0, (float) $result['score']));
    }
}
