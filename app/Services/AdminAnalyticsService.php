<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Assessment;
use App\Models\LearningRiskScore;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ticket R4 — analytics tong quan cho admin (SPEC §8 R4).
 * Cohort co ban + thong ke van hanh. Query gom, khong N+1.
 */
class AdminAnalyticsService
{
    public function overview(): array
    {
        return [
            'users' => $this->userStats(),
            'funnel' => $this->funnel(),
            'risk' => $this->riskDistribution(),
            'ai' => $this->aiStats(),
            'revenue' => $this->revenueStats(),
        ];
    }

    /** Bao cao DAY DU cho trang report admin — them system counts, top hoc sinh, giao dich. */
    public function fullReport(): array
    {
        return array_merge($this->overview(), [
            'system' => $this->systemCounts(),
            'learning' => $this->learningStats(),
            'top_students' => $this->topStudents(),
            'recent_payments' => $this->recentPayments(),
        ]);
    }

    /** Dem moi thuc the chinh — "toan bo thong tin". */
    private function systemCounts(): array
    {
        return [
            'assessments' => Assessment::count(),
            'curricula' => \App\Models\Curriculum::count(),
            'lessons' => \App\Models\Lesson::count(),
            'quiz_attempts' => \App\Models\QuizAttempt::whereNotNull('submitted_at')->count(),
            'solver_requests' => \App\Models\SolverRequest::count(),
            'tutor_messages' => \App\Models\TutorMessage::count(),
            'attendance_sessions' => \App\Models\AttendanceSession::count(),
            'activity_logs' => \App\Models\StudentActivityLog::count(),
            'badges_earned' => \App\Models\StudentBadge::count(),
            'classes' => \App\Models\SchoolClass::count(),
            'assignments' => \App\Models\Assignment::count(),
            'plans' => \App\Models\Plan::count(),
        ];
    }

    private function learningStats(): array
    {
        $completed = \App\Models\Lesson::where('status', 'completed')->count();
        $avgQuiz = \App\Models\QuizAttempt::whereNotNull('submitted_at')->avg('score');

        return [
            'lessons_completed' => $completed,
            'avg_quiz_score' => $avgQuiz !== null ? round((float) $avgQuiz, 1) : null,
            'assessments_graded' => Assessment::where('status', 'graded')->count(),
        ];
    }

    /** Top hoc sinh theo diem tich luy (khong N+1). */
    private function topStudents(int $limit = 8): array
    {
        return Student::query()
            ->orderByDesc('points_balance')
            ->limit($limit)
            ->get(['id', 'full_name', 'points_balance', 'streak_days', 'status'])
            ->map(fn ($s, $i) => [
                'rank' => $i + 1,
                'name' => $s->full_name,
                'points' => $s->points_balance,
                'streak' => $s->streak_days,
                'status' => $s->status,
            ])->all();
    }

    /** Giao dich gan nhat (moi trang thai). */
    private function recentPayments(int $limit = 8): array
    {
        return Payment::query()
            ->with('plan:id,name')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'order_id' => $p->order_id,
                'plan' => $p->plan?->name,
                'amount' => $p->amount,
                'gateway' => $p->gateway,
                'status' => $p->status,
                'created_at' => $p->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m H:i'),
            ])->all();
    }

    private function userStats(): array
    {
        // 1 query gom theo role.
        $byRole = User::query()
            ->selectRaw('role, COUNT(*) as n')
            ->groupBy('role')
            ->pluck('n', 'role');

        return [
            'total' => (int) $byRole->sum(),
            'by_role' => $byRole->map(fn ($n) => (int) $n)->all(),
        ];
    }

    /** Phễu chuyển đổi theo state machine — cohort co ban. */
    private function funnel(): array
    {
        $byStatus = Student::query()
            ->selectRaw('status, COUNT(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $order = Student::STATUS_FLOW;
        $funnel = [];
        foreach ($order as $status) {
            $funnel[$status] = (int) ($byStatus[$status] ?? 0);
        }

        $total = array_sum($funnel);
        $reachedLearning = $funnel[Student::STATUS_LEARNING] ?? 0;

        return [
            'stages' => $funnel,
            'assessment_count' => Assessment::where('status', 'graded')->count(),
            'conversion_to_learning' => $total > 0 ? round($reachedLearning / $total * 100, 1) : 0,
        ];
    }

    private function riskDistribution(): array
    {
        // Risk moi nhat cua moi hoc sinh -> phan bo 3 muc.
        $latest = LearningRiskScore::query()
            ->selectRaw('student_id, MAX(computed_at) as latest')
            ->groupBy('student_id');

        $levels = DB::table('learning_risk_scores as r')
            ->joinSub($latest, 'l', function ($join) {
                $join->on('r.student_id', '=', 'l.student_id')
                    ->on('r.computed_at', '=', 'l.latest');
            })
            ->selectRaw('r.level, COUNT(*) as n')
            ->groupBy('r.level')
            ->pluck('n', 'level');

        return [
            'on_dinh' => (int) ($levels['on_dinh'] ?? 0),
            'can_theo_doi' => (int) ($levels['can_theo_doi'] ?? 0),
            'nguy_co_cao' => (int) ($levels['nguy_co_cao'] ?? 0),
        ];
    }

    private function aiStats(): array
    {
        $last7 = AiLog::where('created_at', '>=', now()->subDays(7));

        return [
            'calls_7d' => (clone $last7)->count(),
            'errors_7d' => (clone $last7)->where('status', 'error')->count(),
            'avg_latency_ms' => (int) round((clone $last7)->where('status', 'ok')->avg('latency_ms') ?? 0),
            'by_feature' => (clone $last7)
                ->selectRaw('feature, COUNT(*) as n')
                ->groupBy('feature')
                ->pluck('n', 'feature')
                ->map(fn ($n) => (int) $n)->all(),
        ];
    }

    private function revenueStats(): array
    {
        $paid = Payment::where('status', 'paid');

        return [
            'paid_count' => (clone $paid)->count(),
            'revenue_total' => (int) (clone $paid)->sum('amount'),
            'revenue_30d' => (int) (clone $paid)->where('paid_at', '>=', now()->subDays(30))->sum('amount'),
        ];
    }
}
