<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AttendanceSession;
use App\Models\ClassificationTopicAbility;
use App\Models\Curriculum;
use App\Models\CurriculumModule;
use App\Models\Exercise;
use App\Models\LearningRiskScore;
use App\Models\Lesson;
use App\Models\PointLedger;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Models\StudentActivityLog;
use App\Models\StudentClassification;
use Illuminate\Database\Seeder;

/**
 * Ticket F4 — student1 co san curriculum + lich su 2 tuan.
 * Du de test: dashboard, mastery grid, recommendation, attendance 3 trang thai,
 * effective study time, va risk score ra muc `can_theo_doi` (vang).
 *
 * student1 = Nguyen Van An, lop 7, tu khai `trung_binh`, gpa 4.5.
 * Co tinh de tang 2 LAT NGUOC tang 1: gpa 4.5 -> base_level `trung_binh`,
 * nhung lam test tot hon ky vong -> final_level `kha`. Dung ca kich ban ma tai
 * lieu goc neu: "co hoc sinh diem 6 nhung tu duy tot, chi yeu trinh bay".
 */
class StudentHistorySeeder extends Seeder
{
    /** [phase, topic, so lesson] — 18 lesson tong. */
    private const MODULES = [
        [Curriculum::PHASE_ON_NEN_TANG, 'phan_so',            3],
        [Curriculum::PHASE_ON_NEN_TANG, 'so_nguyen',          3],
        [Curriculum::PHASE_CUNG_CO,     'ty_le_thuc',         3],
        [Curriculum::PHASE_CUNG_CO,     'bieu_thuc_dai_so',   3],
        [Curriculum::PHASE_NANG_CAO,    'hinh_hoc_phang',     3],
        [Curriculum::PHASE_LUYEN_DE,    'luyen_de_tong_hop',  3],
    ];

    /** Nang luc theo chuyen de: [topic, ability, error_rate]. */
    private const TOPIC_ABILITIES = [
        ['phan_so',           35, 62.50],   // yeu
        ['so_nguyen',         42, 55.00],   // yeu
        ['ty_le_thuc',        58, 38.00],
        ['bieu_thuc_dai_so',  61, 33.50],
        ['hinh_hoc_phang',    70, 24.00],
    ];

    /** Diem quiz 13 buoi — 3 buoi cuoi giam lien tiep de kich hoat canh bao. */
    private const QUIZ_SCORES = [
        6.0, 7.0, 6.5, 8.0, 7.5, 8.5, 7.0, 8.0, 9.0, 7.5,
        6.5, 5.5, 4.5,
    ];

    private const SESSION_STANDARD_MINUTES = 45;

    public function run(): void
    {
        $student = Student::whereHas('user', fn ($q) => $q->where('email', 'student1@hoctoan.test'))
            ->firstOrFail();

        $assessment     = $this->seedAssessment($student);
        $classification = $this->seedClassification($student, $assessment);
        $curriculum     = $this->seedCurriculum($student, $classification);
        $lessons        = $this->seedModulesAndLessons($curriculum);

        $this->seedQuizHistory($student, $lessons);
        $this->seedAttendance($student, $lessons);
        $this->seedRiskScore($student);

        $student->update([
            'status'         => Student::STATUS_LEARNING,
            'streak_days'    => 4,
            'points_balance' => PointLedger::where('student_id', $student->id)->sum('amount'),
        ]);
    }

    private function seedAssessment(Student $student): Assessment
    {
        $assessment = Assessment::create([
            'student_id'   => $student->id,
            'status'       => Assessment::STATUS_GRADED,
            'score'        => 6.4,
            'started_at'   => now()->subDays(15),
            'submitted_at' => now()->subDays(15)->addMinutes(28),
        ]);

        $questions = [
            ['phan_so',          'multiple_choice', 'easy',   true,  45],
            ['phan_so',          'multiple_choice', 'medium', false, 132],
            ['phan_so',          'essay',           'hard',   false, 210],
            ['so_nguyen',        'multiple_choice', 'easy',   true,  38],
            ['so_nguyen',        'multiple_choice', 'medium', false, 145],
            ['ty_le_thuc',       'multiple_choice', 'medium', true,  88],
            ['bieu_thuc_dai_so', 'essay',           'medium', true,  165],
            ['hinh_hoc_phang',   'multiple_choice', 'hard',   true,  120],
        ];

        foreach ($questions as $i => [$topic, $type, $difficulty, $correct, $seconds]) {
            AssessmentQuestion::create([
                'assessment_id'      => $assessment->id,
                'question_order'     => $i + 1,
                'type'               => $type,
                'topic'              => $topic,
                'difficulty'         => $difficulty,
                'content'            => "Cau {$i}: de bai mau cho chuyen de {$topic}.",
                'options'            => $type === 'multiple_choice' ? ['A', 'B', 'C', 'D'] : null,
                'correct_answer'     => ['value' => 'A'],
                'student_answer'     => ['value' => $correct ? 'A' : 'B'],
                'is_correct'         => $correct,
                'time_spent_seconds' => $seconds,
            ]);
        }

        PointLedger::create([
            'student_id' => $student->id,
            'amount'     => 50,
            'reason'     => PointLedger::REASON_ASSESSMENT_COMPLETE,
            'ref_id'     => $assessment->id,
        ]);

        return $assessment;
    }

    private function seedClassification(Student $student, Assessment $assessment): StudentClassification
    {
        // Tang 1: gpa 4.5 <= 5 -> trung_binh (CHI THAM KHAO).
        // Tang 2: lam test tot hon ky vong -> kha (QUYET DINH).
        $classification = StudentClassification::create([
            'student_id'          => $student->id,
            'assessment_id'       => $assessment->id,
            'overall_ability'     => 58,
            'self_learning_level' => 55,
            'processing_speed'    => 48,
            'base_level'          => StudentClassification::LEVEL_TRUNG_BINH,
            'final_level'         => StudentClassification::LEVEL_KHA,
            'weak_topics'         => ['phan_so', 'so_nguyen'],
        ]);

        foreach (self::TOPIC_ABILITIES as [$topic, $ability, $errorRate]) {
            ClassificationTopicAbility::create([
                'classification_id' => $classification->id,
                'topic'             => $topic,
                'ability'           => $ability,
                'error_rate'        => $errorRate,
            ]);
        }

        return $classification;
    }

    private function seedCurriculum(Student $student, StudentClassification $classification): Curriculum
    {
        return Curriculum::create([
            'student_id'        => $student->id,
            'classification_id' => $classification->id,
            'status'            => 'active',
            'goal'              => 'Lay lai nen tang phan so va so nguyen trong 20 buoi',
            'planned_sessions'  => 20,
        ]);
    }

    /** @return array<int, Lesson> 18 lesson: 13 completed, 1 unlocked, 4 locked. */
    private function seedModulesAndLessons(Curriculum $curriculum): array
    {
        $lessons = [];
        $n       = 0;

        foreach (self::MODULES as $order => [$phase, $topic, $lessonCount]) {
            $module = CurriculumModule::create([
                'curriculum_id' => $curriculum->id,
                'phase'         => $phase,
                'topic'         => $topic,
                'module_order'  => $order + 1,
            ]);

            for ($i = 1; $i <= $lessonCount; $i++) {
                $n++;

                $status = match (true) {
                    $n <= 13 => Lesson::STATUS_COMPLETED,
                    $n === 14 => Lesson::STATUS_UNLOCKED,
                    default   => Lesson::STATUS_LOCKED,
                };

                $lesson = Lesson::create([
                    'module_id'      => $module->id,
                    'lesson_order'   => $i,
                    'title'          => "Buoi {$n}: ".str_replace('_', ' ', $topic)." - phan {$i}",
                    'theory_content' => "Ly thuyet toi gian cho {$topic}, phan {$i}.",
                    'status'         => $status,
                ]);

                foreach (['easy', 'medium', 'hard'] as $difficulty) {
                    Exercise::create([
                        'lesson_id'  => $lesson->id,
                        'difficulty' => $difficulty,
                        'content'    => "Bai tap {$difficulty} - {$topic}",
                        'answer'     => ['value' => 42],
                    ]);
                }

                Quiz::create([
                    'lesson_id'        => $lesson->id,
                    'duration_minutes' => config('hoctoan.quiz.duration_minutes'),
                ]);

                $lessons[$n] = $lesson;
            }
        }

        return $lessons;
    }

    /** 13 quiz da lam, 3 buoi cuoi diem giam lien tiep (7.5 -> 6.5 -> 5.5 -> 4.5). */
    private function seedQuizHistory(Student $student, array $lessons): void
    {
        $reviewMin = config('hoctoan.quiz.review_min');

        foreach (self::QUIZ_SCORES as $i => $score) {
            $lesson = $lessons[$i + 1];
            $quiz   = $lesson->quiz;
            $day    = 14 - $i;

            $startedAt = now()->subDays($day)->setTime(19, 0);

            QuizAttempt::create([
                'quiz_id'        => $quiz->id,
                'student_id'     => $student->id,
                'score'          => $score,
                'error_analysis' => ['weak_topic' => $lesson->module->topic, 'wrong' => 10 - (int) $score],
                'suggestion'     => $score < $reviewMin
                    ? QuizAttempt::SUGGESTION_ON_LAI
                    : QuizAttempt::SUGGESTION_HOC_TIEP,
                'started_at'     => $startedAt,
                'expires_at'     => $startedAt->copy()->addMinutes($quiz->duration_minutes),
                'submitted_at'   => $startedAt->copy()->addMinutes(12),
            ]);

            PointLedger::create([
                'student_id' => $student->id,
                'amount'     => (int) round($score * 2),
                'reason'     => PointLedger::REASON_QUIZ_SCORE,
                'ref_id'     => $quiz->id,
            ]);
        }
    }

    /**
     * 14 buoi = 2 tuan: 7 present, 4 partial, 3 absent.
     * Buoi partial co truong hop kinh dien cua SPEC §3.5:
     * online 90' nhung hoc thuc chi 8' -> muc tap trung thap.
     */
    private function seedAttendance(Student $student, array $lessons): void
    {
        // [so ngay truoc, trang thai, hoc thuc (phut), online (phut)]
        $plan = [
            [14, AttendanceSession::STATUS_PRESENT, 42, 50],
            [13, AttendanceSession::STATUS_PRESENT, 38, 45],
            [12, AttendanceSession::STATUS_PARTIAL, 18, 40],
            [11, AttendanceSession::STATUS_PRESENT, 45, 52],
            [10, AttendanceSession::STATUS_ABSENT,   0,  0],
            [9,  AttendanceSession::STATUS_PRESENT, 40, 47],
            [8,  AttendanceSession::STATUS_PARTIAL,  8, 90],   // truong hop kinh dien
            [7,  AttendanceSession::STATUS_PRESENT, 41, 48],
            [6,  AttendanceSession::STATUS_PRESENT, 44, 49],
            [5,  AttendanceSession::STATUS_ABSENT,   0,  0],
            [4,  AttendanceSession::STATUS_ABSENT,   0,  0],   // vang 2 buoi lien tiep -> alert cao
            [3,  AttendanceSession::STATUS_PARTIAL, 22, 35],
            [2,  AttendanceSession::STATUS_PRESENT, 39, 44],
            [1,  AttendanceSession::STATUS_PARTIAL, 15, 30],
        ];

        foreach ($plan as $i => [$daysAgo, $status, $effective, $online]) {
            $lesson    = $lessons[min($i + 1, count($lessons))];
            $scheduled = now()->subDays($daysAgo)->setTime(19, 0);
            $attended  = $status !== AttendanceSession::STATUS_ABSENT;

            $session = AttendanceSession::create([
                'student_id'              => $student->id,
                'lesson_id'               => $lesson->id,
                'scheduled_start_time'    => $scheduled,
                'actual_start_time'       => $attended ? $scheduled->copy()->addMinutes(2) : null,
                'actual_end_time'         => $attended ? $scheduled->copy()->addMinutes(2 + $online) : null,
                'attendance_status'       => $status,
                'effective_study_minutes' => $effective,
                'idle_minutes'            => max(0, $online - $effective),
                'completion_rate'         => $online > 0
                    ? round(min(100, $effective / self::SESSION_STANDARD_MINUTES * 100), 2)
                    : 0,
            ]);

            if ($attended) {
                $this->seedActivityLogs($session);
            }
        }
    }

    private function seedActivityLogs(AttendanceSession $session): void
    {
        $events = ['lesson_open', 'section_view', 'exercise_start', 'answer_submit', 'quiz_submit'];
        $at     = $session->actual_start_time->copy();

        foreach ($events as $event) {
            StudentActivityLog::create([
                'session_id' => $session->id,
                'event_type' => $event,
                'event_time' => $at->copy(),
                'metadata'   => ['seeded' => true],
            ]);

            $at->addMinutes(2);
        }
    }

    /**
     * Risk score theo dung cong thuc SPEC §3.6, trong so doc tu config.
     * Ket qua ~35 -> `can_theo_doi` (vang) — trang thai co ich de demo parent dashboard.
     */
    private function seedRiskScore(Student $student): void
    {
        $components = [
            'absenteeism'           => 21.43,   // 3/14 buoi
            'incomplete_session'    => 28.57,   // 4/14 buoi partial
            'low_engagement'        => 45.00,
            'quiz_decline'          => 60.00,   // 3 buoi giam lien tiep
            'missed_recommendation' => 30.00,
        ];

        $weights = config('hoctoan.risk_weights');
        $score   = 0.0;

        foreach ($components as $key => $rate) {
            $score += $weights[$key] * $rate;
        }

        $rounded = (int) round($score);

        LearningRiskScore::create([
            'student_id'  => $student->id,
            'risk_score'  => $rounded,
            'level'       => LearningRiskScore::levelFor($rounded),
            'components'  => $components,
            'computed_at' => now()->subHours(6),
        ]);
    }
}
