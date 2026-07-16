<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Ticket C3 — tao de kiem tra dau vao (SPEC §2.3, §3.2).
 *
 * A.I sinh 5-10 cau (mix trac nghiem/tu luan), phu chu de nen tang cua khoi lop,
 * dua tren self_assessed_level + math_gpa. De "vua suc nhung du phan loai".
 *
 * time_spent_seconds do frontend track tung cau -> input BAT BUOC cua phan loai
 * tang 2 (C4). Khong co no thi tang 2 mat 1 tin hieu chinh.
 */
class AssessmentService
{
    /** JSON Schema cho de A.I sinh — ep du field, C4 dua vao. */
    public const QUESTION_SCHEMA = [
        'type' => 'object',
        'required' => ['questions'],
        'properties' => [
            'questions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'required' => ['type', 'topic', 'difficulty', 'content', 'correct_answer'],
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['multiple_choice', 'essay']],
                        'topic' => ['type' => 'string'],
                        'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
                        'content' => ['type' => 'string'],
                        'options' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'correct_answer' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    public function __construct(private readonly AiProviderService $ai) {}

    /**
     * Bat dau bai danh gia: sinh de + tao ban ghi. Neu dang co bai in_progress
     * thi tra lai bai do (khong tao trung).
     */
    public function start(Student $student): Assessment
    {
        $existing = $student->assessments()
            ->where('status', Assessment::STATUS_IN_PROGRESS)
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $payload = $this->ai->chat(
            AiLog::FEATURE_ASSESSMENT_GEN,
            $this->buildPrompt($student),
            self::QUESTION_SCHEMA,
            $student->id,
        );

        $questions = $this->normalize($payload['questions']);

        return DB::transaction(function () use ($student, $questions) {
            $assessment = Assessment::create([
                'student_id' => $student->id,
                'status' => Assessment::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            foreach ($questions as $order => $q) {
                AssessmentQuestion::create([
                    'assessment_id' => $assessment->id,
                    'question_order' => $order + 1,
                    'type' => $q['type'],
                    'topic' => $q['topic'],
                    'difficulty' => $q['difficulty'],
                    'content' => $q['content'],
                    'options' => $q['options'] ?? null,
                    'correct_answer' => ['value' => $q['correct_answer']],
                    'time_spent_seconds' => 0,
                ]);
            }

            return $assessment->load('questions');
        });
    }

    /**
     * Luu tam bai lam (autosave 30s). Ghi student_answer + cong don time_spent.
     * KHONG cham o day — cham khi submit (C4).
     *
     * @param array<int, array{answer: mixed, time_spent_seconds: int}> $answers  keyed by question_id
     */
    public function saveProgress(Assessment $assessment, array $answers): void
    {
        DB::transaction(function () use ($assessment, $answers) {
            foreach ($assessment->questions as $question) {
                if (! isset($answers[$question->id])) {
                    continue;
                }

                $input = $answers[$question->id];

                $question->update([
                    'student_answer' => ['value' => $input['answer'] ?? null],
                    // Cong don, khong ghi de: hoc sinh quay lai cau cu thi thoi gian tich luy.
                    'time_spent_seconds' => max(
                        $question->time_spent_seconds,
                        (int) ($input['time_spent_seconds'] ?? 0),
                    ),
                ]);
            }
        });
    }

    /** Chuan hoa: dam bao 5-10 cau. AI tra thua thi cat, thieu thi giu nguyen (>=1). */
    private function normalize(array $questions): array
    {
        $questions = array_values(array_filter($questions, fn ($q) => ! empty($q['content'])));

        return array_slice($questions, 0, 10);
    }

    private function buildPrompt(Student $student): string
    {
        return <<<PROMPT
        Bạn là giáo viên toán. Hãy tạo một đề kiểm tra đầu vào cho học sinh lớp {$student->grade}.

        Thông tin học sinh:
        - Khối lớp: {$student->grade}
        - Học lực tự đánh giá: {$student->self_assessed_level}
        - Điểm trung bình môn toán: {$student->math_gpa}
        - Trường: {$student->school_name}

        Yêu cầu đề:
        - Gồm 8 câu, trộn trắc nghiệm (multiple_choice, có 4 lựa chọn) và tự luận (essay).
        - Phủ các chủ đề nền tảng của lớp {$student->grade} (mỗi câu ghi rõ topic bằng slug tiếng Việt không dấu, ví dụ: phan_so, so_nguyen, ty_le_thuc).
        - Độ khó vừa sức nhưng đủ để phân loại năng lực (trộn easy/medium/hard).
        - correct_answer: với trắc nghiệm ghi chữ cái đáp án đúng (A/B/C/D); với tự luận ghi đáp án ngắn gọn.

        Trả về JSON đúng schema.
        PROMPT;
    }
}
