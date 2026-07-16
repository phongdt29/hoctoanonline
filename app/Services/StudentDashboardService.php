<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\Student;

/**
 * Ticket L4 — gom du lieu dashboard hoc sinh trong 1 request (khong N+1).
 * Eager-load san, tinh toan tren collection da nap.
 */
class StudentDashboardService
{
    public function __construct(private readonly RecommendationService $recommendations) {}

    public function build(Student $student): array
    {
        // 1 lan nap curriculum + modules + lessons (khong query trong vong lap).
        $curriculum = $student->activeCurriculum()
            ->with(['modules.lessons'])
            ->first();

        $lessons = $curriculum
            ? $curriculum->modules->flatMap->lessons
            : collect();

        $done = $lessons->where('status', Lesson::STATUS_COMPLETED)->count();
        $total = $lessons->count();

        $avgScore = QuizAttempt::where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->avg('score');

        return [
            'completion_percent' => $total > 0 ? round($done / $total * 100) : 0,
            'sessions_done' => $done,
            'sessions_remaining' => $total - $done,
            'avg_quiz_score' => $avgScore !== null ? round((float) $avgScore, 1) : null,
            'weak_topics' => $student->latestClassification?->weak_topics ?? [],
            'points_balance' => $student->points_balance,
            'streak_days' => $student->streak_days,
            'today_recommendation' => $this->recommendations->recommend($student),
        ];
    }
}
