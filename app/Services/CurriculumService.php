<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Curriculum;
use App\Models\CurriculumModule;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\StudentClassification;
use Illuminate\Support\Facades\DB;

/**
 * Ticket C5 + SPEC §3.2 — sinh giao trinh tu VECTOR NANG LUC (topic_abilities),
 * KHONG tu 1 nhan final_level.
 *
 * 4 phase: 1 on_nen_tang | 2 cung_co | 3 nang_cao | 4 luyen_de.
 * Phase 1 UU TIEN weak_topics. Moi lesson: >= 3 exercise du 3 muc + 1 quiz 15'.
 * Lesson dau unlocked, con lai locked (mo tuan tu khi lesson truoc completed).
 */
class CurriculumService
{
    public const SCHEMA = [
        'type' => 'object',
        'required' => ['goal', 'planned_sessions', 'modules'],
        'properties' => [
            'goal' => ['type' => 'string'],
            'planned_sessions' => ['type' => 'integer'],
            'modules' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'required' => ['phase', 'topic', 'lessons'],
                    'properties' => [
                        'phase' => ['type' => 'integer'],
                        'topic' => ['type' => 'string'],
                        'lessons' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'required' => ['title', 'theory', 'exercises'],
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'theory' => ['type' => 'string'],
                                    'exercises' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'required' => ['difficulty', 'content', 'answer'],
                                            'properties' => [
                                                'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
                                                'content' => ['type' => 'string'],
                                                'answer' => ['type' => 'string'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function __construct(private readonly AiProviderService $ai) {}

    public function generate(StudentClassification $classification): Curriculum
    {
        $student = $classification->student;
        $classification->loadMissing('topicAbilities');

        $result = $this->ai->chat(
            AiLog::FEATURE_CURRICULUM,
            $this->buildPrompt($student, $classification),
            self::SCHEMA,
            $student->id,
        );

        return DB::transaction(function () use ($student, $classification, $result) {
            $curriculum = Curriculum::create([
                'student_id' => $student->id,
                'classification_id' => $classification->id,
                'status' => 'active',
                'goal' => $result['goal'],
                'planned_sessions' => max(1, (int) $result['planned_sessions']),
            ]);

            $this->buildModules($curriculum, $result['modules']);
            $this->unlockFirstLesson($curriculum);

            $student->update(['status' => Student::STATUS_CURRICULUM_ACTIVE]);

            return $curriculum->load('modules.lessons');
        });
    }

    /**
     * Chen 1 buoi on vao giao trinh khi recommendation yeu cau (ticket L3).
     * Chung minh curriculum DONG, khong lap san cung.
     */
    public function insertReviewLesson(Curriculum $curriculum, string $topic, string $title, string $theory): Lesson
    {
        $module = $curriculum->modules()
            ->where('phase', Curriculum::PHASE_ON_NEN_TANG)
            ->where('topic', $topic)
            ->first();

        if (! $module) {
            $module = CurriculumModule::create([
                'curriculum_id' => $curriculum->id,
                'phase' => Curriculum::PHASE_ON_NEN_TANG,
                'topic' => $topic,
                'module_order' => ($curriculum->modules()->max('module_order') ?? 0) + 1,
            ]);
        }

        return Lesson::create([
            'module_id' => $module->id,
            'lesson_order' => ($module->lessons()->max('lesson_order') ?? 0) + 1,
            'title' => $title,
            'theory_content' => $theory,
            'status' => Lesson::STATUS_UNLOCKED,   // buoi on chen vao la hoc ngay
        ]);
    }

    private function buildModules(Curriculum $curriculum, array $modules): void
    {
        foreach ($modules as $mOrder => $module) {
            $curriculumModule = CurriculumModule::create([
                'curriculum_id' => $curriculum->id,
                'phase' => max(1, min(4, (int) $module['phase'])),
                'topic' => $module['topic'],
                'module_order' => $mOrder + 1,
            ]);

            foreach ($module['lessons'] as $lOrder => $lessonData) {
                $lesson = Lesson::create([
                    'module_id' => $curriculumModule->id,
                    'lesson_order' => $lOrder + 1,
                    'title' => $lessonData['title'],
                    'theory_content' => $lessonData['theory'],
                    'status' => Lesson::STATUS_LOCKED,
                ]);

                $this->buildExercises($lesson, $lessonData['exercises']);

                Quiz::create([
                    'lesson_id' => $lesson->id,
                    'duration_minutes' => config('hoctoan.quiz.duration_minutes'),
                ]);
            }
        }
    }

    /** Dam bao moi lesson co du 3 muc de/tb/kho (DoD C5). AI thieu muc nao -> tu bu. */
    private function buildExercises(Lesson $lesson, array $exercises): void
    {
        $byDifficulty = collect($exercises)->keyBy('difficulty');

        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $ex = $byDifficulty->get($difficulty, [
                'content' => 'Bài tập mức '.$difficulty.' cho '.$lesson->title,
                'answer' => '',
            ]);

            Exercise::create([
                'lesson_id' => $lesson->id,
                'difficulty' => $difficulty,
                'content' => $ex['content'],
                'answer' => ['value' => $ex['answer'] ?? ''],
            ]);
        }
    }

    private function unlockFirstLesson(Curriculum $curriculum): void
    {
        $first = Lesson::query()
            ->whereIn('module_id', $curriculum->modules()->pluck('id'))
            ->orderBy('module_id')
            ->orderBy('lesson_order')
            ->first();

        $first?->update(['status' => Lesson::STATUS_UNLOCKED]);
    }

    private function buildPrompt(Student $student, StudentClassification $classification): string
    {
        $topicLines = $classification->topicAbilities
            ->map(fn ($t) => "- {$t->topic}: năng lực {$t->ability}/100, tỉ lệ sai {$t->error_rate}%")
            ->implode("\n");

        $weak = implode(', ', $classification->weak_topics);

        return <<<PROMPT
        Bạn là chuyên gia thiết kế giáo trình toán cá nhân hóa. Tạo lộ trình cho học sinh lớp {$student->grade}.

        Năng lực học sinh (dựa vào bài test thật, KHÔNG chỉ theo điểm số):
        - Mức tổng quát: {$classification->final_level}
        - Năng lực theo chủ đề:
        {$topicLines}
        - Chủ đề yếu nhất (cần ưu tiên): {$weak}

        Yêu cầu giáo trình:
        - Chia 4 phase: phase 1 = ôn nền tảng, 2 = củng cố, 3 = nâng cao, 4 = luyện đề.
        - PHASE 1 PHẢI ưu tiên các chủ đề yếu ({$weak}).
        - Mỗi module thuộc 1 phase, có nhiều lesson.
        - Mỗi lesson: lý thuyết tối giản (theory) + đúng 3 bài tập (exercises) mức easy/medium/hard.
        - planned_sessions: tổng số buổi hợp lý (10-25).

        Trả về JSON đúng schema.
        PROMPT;
    }
}
