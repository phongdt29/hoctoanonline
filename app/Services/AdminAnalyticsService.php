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
