<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ClassificationTopicAbility;
use App\Models\Curriculum;
use App\Models\CurriculumModule;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\StudentClassification;
use Illuminate\Support\Facades\DB;

/**
 * Sinh lo trinh DEMO (khong goi AI) — cho nut "Bo qua bai test" o moi truong dev.
 * Tao du: assessment(graded) + classification + curriculum + 4 phase + lessons +
 * exercises 3 muc + quiz, de test dashboard/lesson/quiz/recommendation.
 *
 * CHI dung cho dev/test. Noi dung co dinh, khong ca nhan hoa.
 */
class DemoCurriculumService
{
    /** [phase, topic, ten hien thi, so lesson]. */
    private const MODULES = [
        [Curriculum::PHASE_ON_NEN_TANG, 'phan_so', 'Phân số', 2],
        [Curriculum::PHASE_CUNG_CO, 'so_nguyen', 'Số nguyên', 2],
        [Curriculum::PHASE_NANG_CAO, 'bieu_thuc', 'Biểu thức đại số', 1],
        [Curriculum::PHASE_LUYEN_DE, 'luyen_de', 'Luyện đề tổng hợp', 1],
    ];

    public function generate(Student $student): Curriculum
    {
        return DB::transaction(function () use ($student) {
            // Query TUOI (khong dung relationship co the bi cache) -> khong tao lo trinh trung.
            $existing = Curriculum::where('student_id', $student->id)
                ->where('status', 'active')
                ->first();

            if ($existing) {
                $student->update(['status' => Student::STATUS_LEARNING]);

                return $existing;
            }

            $assessment = Assessment::create([
                'student_id' => $student->id,
                'status' => Assessment::STATUS_GRADED,
                'score' => 6.5,
                'started_at' => now()->subMinutes(20),
                'submitted_at' => now()->subMinutes(2),
            ]);

            $classification = StudentClassification::create([
                'student_id' => $student->id,
                'assessment_id' => $assessment->id,
                'overall_ability' => 60,
                'self_learning_level' => 55,
                'processing_speed' => 58,
                'base_level' => StudentClassification::LEVEL_KHA,
                'final_level' => StudentClassification::LEVEL_KHA,
                'weak_topics' => ['phan_so', 'so_nguyen'],
            ]);

            foreach ([['phan_so', 40, 60.0], ['so_nguyen', 48, 52.0], ['bieu_thuc', 65, 30.0]] as [$t, $a, $e]) {
                ClassificationTopicAbility::create([
                    'classification_id' => $classification->id,
                    'topic' => $t, 'ability' => $a, 'error_rate' => $e,
                ]);
            }

            $curriculum = Curriculum::create([
                'student_id' => $student->id,
                'classification_id' => $classification->id,
                'status' => 'active',
                'goal' => 'Lộ trình demo — dùng để kiểm thử tính năng học.',
                'planned_sessions' => 6,
            ]);

            $this->buildModules($curriculum);
            $this->unlockFirstLesson($curriculum);

            $student->update(['status' => Student::STATUS_LEARNING]);

            return $curriculum;
        });
    }

    private function buildModules(Curriculum $curriculum): void
    {
        foreach (self::MODULES as $order => [$phase, $topic, $name, $count]) {
            $module = CurriculumModule::create([
                'curriculum_id' => $curriculum->id,
                'phase' => $phase,
                'topic' => $topic,
                'module_order' => $order + 1,
            ]);

            for ($i = 1; $i <= $count; $i++) {
                $lesson = Lesson::create([
                    'module_id' => $module->id,
                    'lesson_order' => $i,
                    'title' => "{$name} — buổi {$i}",
                    'theory_content' => "Đây là phần lý thuyết demo cho chủ đề {$name}. "
                        ."Nội dung tối giản để bạn kiểm thử giao diện học, làm bài tập và quiz.",
                    'status' => Lesson::STATUS_LOCKED,
                ]);

                foreach (['easy', 'medium', 'hard'] as $d) {
                    Exercise::create([
                        'lesson_id' => $lesson->id,
                        'difficulty' => $d,
                        'content' => "Bài tập mức {$d} cho {$name}: ví dụ minh họa.",
                        'answer' => ['value' => '42'],
                    ]);
                }

                Quiz::create([
                    'lesson_id' => $lesson->id,
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
}
