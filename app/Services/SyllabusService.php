<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Syllabus;

/**
 * Sinh giao trinh mau bang AI (dung chung, khong gan hoc sinh).
 *
 * Tai dung CurriculumService::SCHEMA (modules -> lessons -> theory + exercises),
 * chi khac prompt: sinh theo lop + chu de + muc tieu, khong theo nang luc 1 hoc sinh.
 * Chay trong GenerateSyllabusJob (nen) vi sinh day du rat ton token.
 */
class SyllabusService
{
    public function __construct(private readonly AiProviderService $ai) {}

    /** Sinh noi dung, luu vao $syllabus->content, danh dau ready. Nem exception neu loi (job retry). */
    public function generate(Syllabus $syllabus): void
    {
        $result = $this->ai->chat(
            AiLog::FEATURE_CURRICULUM,
            $this->buildPrompt($syllabus),
            CurriculumService::SCHEMA,
        );

        $syllabus->update([
            'content'          => $this->normalize($result),
            'goal'             => $syllabus->goal ?: ($result['goal'] ?? null),
            'planned_sessions' => max(1, (int) ($result['planned_sessions'] ?? $syllabus->planned_sessions)),
            'status'           => Syllabus::STATUS_READY,
            'error'            => null,
            'generated_at'     => now(),
        ]);
    }

    /**
     * Chuan hoa ket qua AI ve dung khung, bu du 3 muc bai tap moi bai (nhu CurriculumService).
     *
     * @param  array<string,mixed>  $result
     * @return array<string,mixed>
     */
    private function normalize(array $result): array
    {
        $modules = [];

        foreach ($result['modules'] ?? [] as $mOrder => $module) {
            $lessons = [];

            foreach ($module['lessons'] ?? [] as $lOrder => $lesson) {
                $byDiff = collect($lesson['exercises'] ?? [])->keyBy('difficulty');
                $exercises = [];

                foreach (['easy', 'medium', 'hard'] as $difficulty) {
                    $ex = $byDiff->get($difficulty);
                    $exercises[] = [
                        'difficulty' => $difficulty,
                        'content'    => trim((string) ($ex['content'] ?? "Bài tập mức {$difficulty}")),
                        'answer'     => trim((string) ($ex['answer'] ?? '')),
                    ];
                }

                $lessons[] = [
                    'order'    => $lOrder + 1,
                    'title'    => trim((string) ($lesson['title'] ?? 'Bài học')),
                    'theory'   => trim((string) ($lesson['theory'] ?? '')),
                    'exercises' => $exercises,
                ];
            }

            $modules[] = [
                'order'   => $mOrder + 1,
                'phase'   => max(1, min(4, (int) ($module['phase'] ?? 1))),
                'topic'   => trim((string) ($module['topic'] ?? 'Chủ đề')),
                'lessons' => $lessons,
            ];
        }

        return [
            'goal'             => $result['goal'] ?? null,
            'planned_sessions' => max(1, (int) ($result['planned_sessions'] ?? 10)),
            'modules'          => $modules,
        ];
    }

    private function buildPrompt(Syllabus $syllabus): string
    {
        $topic = $syllabus->topic ? "Chủ đề trọng tâm: {$syllabus->topic}." : 'Bao quát chương trình của lớp.';
        $goal  = $syllabus->goal ? "Mục tiêu: {$syllabus->goal}." : '';
        $sessions = $syllabus->planned_sessions > 0
            ? "Khoảng {$syllabus->planned_sessions} buổi."
            : 'Số buổi hợp lý (10-20).';

        return <<<PROMPT
        Bạn là chuyên gia thiết kế giáo trình Toán. Hãy soạn một GIÁO TRÌNH MẪU dùng chung cho học sinh lớp {$syllabus->grade} Việt Nam.
        {$topic}
        {$goal}
        Quy mô: {$sessions}

        Yêu cầu:
        - Chia 4 phase: phase 1 = ôn nền tảng, 2 = củng cố, 3 = nâng cao, 4 = luyện đề.
        - Mỗi module thuộc 1 phase (trường "phase"), có "topic" và nhiều "lessons".
        - Mỗi lesson gồm: "title", "theory" (lý thuyết ngắn gọn, dễ hiểu, đúng chương trình lớp {$syllabus->grade}),
          và đúng 3 bài tập "exercises" mức easy/medium/hard, mỗi bài có "content" và "answer".
        - Công thức toán viết bằng LaTeX đặt trong \$...\$ (vd \$x^2+1\$).
        - Nội dung tiếng Việt, bám sát chương trình phổ thông.
        - "planned_sessions": tổng số buổi hợp lý.

        Trả về JSON đúng schema.
        PROMPT;
    }
}
