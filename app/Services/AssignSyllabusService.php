<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\CurriculumModule;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\StudentClassification;
use App\Models\Syllabus;
use Illuminate\Support\Facades\DB;

/**
 * Gan giao trinh mau cho hoc sinh — clone content (JSON) sang lo trinh THAT
 * (curricula -> modules -> lessons -> exercises -> quiz) de hoc sinh hoc theo.
 *
 * Lo trinh cu dang active bi archive. Neu hoc sinh chua tung phan loai
 * (chua lam bai test) -> tao 1 classification toi gian (bang curricula bat buoc classification_id).
 */
class AssignSyllabusService
{
    public function assign(Syllabus $syllabus, Student $student): Curriculum
    {
        return DB::transaction(function () use ($syllabus, $student) {
            // Lo trinh cu -> archive de chi con 1 active.
            Curriculum::where('student_id', $student->id)
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            $classification = $student->latestClassification()->first()
                ?? $this->placeholderClassification($student);

            $curriculum = Curriculum::create([
                'student_id'       => $student->id,
                'classification_id' => $classification->id,
                'status'           => 'active',
                'goal'             => $syllabus->goal ?: ('Giáo trình: '.$syllabus->title),
                'planned_sessions' => max(1, $syllabus->planned_sessions ?: $syllabus->lessonCount()),
            ]);

            $this->buildFromContent($curriculum, $syllabus->content['modules'] ?? []);
            $this->unlockFirstLesson($curriculum);

            $student->update(['status' => Student::STATUS_LEARNING]);

            return $curriculum;
        });
    }

    /** @param  array<int,array<string,mixed>>  $modules */
    private function buildFromContent(Curriculum $curriculum, array $modules): void
    {
        foreach ($modules as $mOrder => $module) {
            $curriculumModule = CurriculumModule::create([
                'curriculum_id' => $curriculum->id,
                'phase'         => max(1, min(4, (int) ($module['phase'] ?? 1))),
                'topic'         => (string) ($module['topic'] ?? 'Chủ đề'),
                'module_order'  => $mOrder + 1,
            ]);

            foreach ($module['lessons'] ?? [] as $lOrder => $lessonData) {
                $lesson = Lesson::create([
                    'module_id'      => $curriculumModule->id,
                    'lesson_order'   => $lOrder + 1,
                    'title'          => (string) ($lessonData['title'] ?? 'Bài học'),
                    'theory_content' => (string) ($lessonData['theory'] ?? ''),
                    'status'         => Lesson::STATUS_LOCKED,
                ]);

                foreach ($lessonData['exercises'] ?? [] as $ex) {
                    Exercise::create([
                        'lesson_id'  => $lesson->id,
                        'difficulty' => in_array($ex['difficulty'] ?? '', ['easy', 'medium', 'hard'], true)
                            ? $ex['difficulty'] : 'medium',
                        'content'    => (string) ($ex['content'] ?? ''),
                        'answer'     => ['value' => (string) ($ex['answer'] ?? '')],
                    ]);
                }

                Quiz::create([
                    'lesson_id'        => $lesson->id,
                    'duration_minutes' => config('hoctoan.quiz.duration_minutes'),
                ]);
            }
        }
    }

    private function unlockFirstLesson(Curriculum $curriculum): void
    {
        $first = Lesson::query()
            ->whereIn('module_id', $curriculum->modules()->pluck('id'))
            ->orderBy('module_id')->orderBy('lesson_order')
            ->first();

        $first?->update(['status' => Lesson::STATUS_UNLOCKED]);
    }

    /** Hoc sinh chua tung phan loai -> tao 1 classification trung tinh (khong tu bai test). */
    private function placeholderClassification(Student $student): StudentClassification
    {
        $assessment = Assessment::create([
            'student_id'   => $student->id,
            'status'       => Assessment::STATUS_GRADED,
            'score'        => 5.0,
            'started_at'   => now(),
            'submitted_at' => now(),
        ]);

        return StudentClassification::create([
            'student_id'          => $student->id,
            'assessment_id'       => $assessment->id,
            'overall_ability'     => 50,
            'self_learning_level' => 50,
            'processing_speed'    => 50,
            'base_level'          => StudentClassification::LEVEL_KHA,
            'final_level'         => StudentClassification::LEVEL_KHA,
            'weak_topics'         => [],
        ]);
    }
}
