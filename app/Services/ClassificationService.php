<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Assessment;
use App\Models\ClassificationTopicAbility;
use App\Models\Student;
use App\Models\StudentClassification;
use Illuminate\Support\Facades\DB;

/**
 * Ticket C4 + SPEC §3.1 — PHAN LOAI 2 TANG. Loi cua ca san pham.
 *
 * Tang 1 (base_level): suy tu math_gpa qua nguong config. CHI THAM KHAO.
 * Tang 2 (final_level): AI hieu chinh tu bai test THAT — diem, time_spent tung cau,
 *   ty le sai theo topic, do on dinh de/kho. QUYET DINH.
 *
 * Vi sao 2 tang: tai lieu goc phe binh "co hoc sinh diem 8 nhung hong nen rat nang".
 * Neu chi dung gpa lam nhan, giao trinh se sinh tu nhan sai -> toan bo phia sau lech.
 * Dau ra KHONG chi 3 nhan ma con: nang luc theo tung chuyen de, muc tu hoc, toc do.
 */
class ClassificationService
{
    public const SCHEMA = [
        'type' => 'object',
        'required' => ['overall_ability', 'self_learning_level', 'processing_speed', 'final_level', 'topic_abilities', 'weak_topics'],
        'properties' => [
            'overall_ability' => ['type' => 'integer'],       // 0..100
            'self_learning_level' => ['type' => 'integer'],   // 0..100
            'processing_speed' => ['type' => 'integer'],      // 0..100
            'final_level' => ['type' => 'string', 'enum' => ['trung_binh', 'kha', 'gioi']],
            'weak_topics' => ['type' => 'array', 'items' => ['type' => 'string']],
            'topic_abilities' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'required' => ['topic', 'ability', 'error_rate'],
                    'properties' => [
                        'topic' => ['type' => 'string'],
                        'ability' => ['type' => 'integer'],     // 0..100
                        'error_rate' => ['type' => 'number'],   // 0..100 (%)
                    ],
                ],
            ],
        ],
    ];

    public function __construct(private readonly AiProviderService $ai) {}

    public function classify(Assessment $assessment): StudentClassification
    {
        $student = $assessment->student;
        $assessment->loadMissing('questions');

        $baseLevel = $this->baseLevel((float) $student->math_gpa);
        $signals = $this->buildSignals($assessment);

        $result = $this->ai->chat(
            AiLog::FEATURE_CURRICULUM,   // phan loai thuoc nhom chuan bi giao trinh
            $this->buildPrompt($student, $baseLevel, $signals),
            self::SCHEMA,
            $student->id,
        );

        return DB::transaction(function () use ($student, $assessment, $baseLevel, $result) {
            $classification = StudentClassification::create([
                'student_id' => $student->id,
                'assessment_id' => $assessment->id,
                'overall_ability' => $this->clamp($result['overall_ability']),
                'self_learning_level' => $this->clamp($result['self_learning_level']),
                'processing_speed' => $this->clamp($result['processing_speed']),
                'base_level' => $baseLevel,
                'final_level' => $result['final_level'],
                'weak_topics' => $result['weak_topics'],
            ]);

            foreach ($result['topic_abilities'] as $ta) {
                ClassificationTopicAbility::create([
                    'classification_id' => $classification->id,
                    'topic' => $ta['topic'],
                    'ability' => $this->clamp($ta['ability']),
                    'error_rate' => max(0, min(100, (float) $ta['error_rate'])),
                ]);
            }

            $student->update(['status' => Student::STATUS_CLASSIFIED]);

            return $classification;
        });
    }

    /** Tang 1: rule cung tu gpa. Nguong tu config (khong hardcode 5/8). */
    public function baseLevel(float $gpa): string
    {
        $t = config('hoctoan.gpa_thresholds');

        return match (true) {
            $gpa <= $t['trung_binh'] => StudentClassification::LEVEL_TRUNG_BINH,
            $gpa <= $t['kha'] => StudentClassification::LEVEL_KHA,
            default => StudentClassification::LEVEL_GIOI,
        };
    }

    /** Tong hop tin hieu tu bai lam that de dua cho AI (tang 2). */
    private function buildSignals(Assessment $assessment): array
    {
        $byTopic = [];

        foreach ($assessment->questions as $q) {
            $topic = $q->topic;
            $byTopic[$topic] ??= ['total' => 0, 'wrong' => 0, 'time' => 0];
            $byTopic[$topic]['total']++;
            $byTopic[$topic]['time'] += $q->time_spent_seconds;

            if ($q->is_correct === false) {
                $byTopic[$topic]['wrong']++;
            }
        }

        return [
            'score' => $assessment->score,
            'by_topic' => $byTopic,
            'total_time' => $assessment->questions->sum('time_spent_seconds'),
            'question_count' => $assessment->questions->count(),
        ];
    }

    private function buildPrompt(Student $student, string $baseLevel, array $signals): string
    {
        $topicLines = collect($signals['by_topic'])
            ->map(fn ($s, $topic) => sprintf(
                '- %s: sai %d/%d câu, tổng thời gian %ds',
                $topic, $s['wrong'], $s['total'], $s['time'],
            ))
            ->implode("\n");

        $gpa = $student->math_gpa;
        $score = $signals['score'];

        return <<<PROMPT
        Bạn là chuyên gia đánh giá năng lực học toán. Phân loại học sinh lớp {$student->grade}.

        QUAN TRỌNG: điểm trung bình môn ({$gpa}) và nhãn sơ bộ từ điểm ({$baseLevel}) CHỈ để tham khảo.
        Điểm trung bình KHÔNG phản ánh đúng năng lực thật (mỗi trường mỗi đề khác nhau, có em điểm
        cao nhưng hổng nền). Hãy dựa CHỦ YẾU vào kết quả bài test thật dưới đây để quyết định.

        Kết quả bài kiểm tra đầu vào:
        - Điểm bài test (thang 10): {$score}
        - Số câu: {$signals['question_count']}, tổng thời gian: {$signals['total_time']}s
        - Chi tiết theo chủ đề:
        {$topicLines}

        Hãy trả về JSON đúng schema, gồm:
        - final_level: nhãn cuối (trung_binh/kha/gioi) — dựa vào bài test thật, có thể KHÁC nhãn sơ bộ.
        - overall_ability (0-100), self_learning_level (0-100), processing_speed (0-100).
        - topic_abilities: mỗi chủ đề xuất hiện trong đề, kèm ability (0-100) và error_rate (0-100).
        - weak_topics: các chủ đề học sinh yếu nhất (mảng slug).
        PROMPT;
    }

    private function clamp(int $value): int
    {
        return max(0, min(100, $value));
    }
}
