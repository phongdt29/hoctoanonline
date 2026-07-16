<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\ParentAccount;
use App\Models\Student;

/**
 * Ticket M4 + SPEC §3 module 9 — Parent Monitoring, 6 khoi bat buoc.
 * Khoi dau = den tin hieu risk (cham mau + chu, khong mau tran).
 */
class ParentDashboardService
{
    public function __construct(private readonly AttendanceService $attendance) {}

    /** Du lieu 6 khoi cho 1 hoc sinh (parent phai da link — kiem tra o Policy/controller). */
    public function forChild(Student $student): array
    {
        $today = AttendanceSession::where('student_id', $student->id)
            ->whereDate('scheduled_start_time', today())
            ->latest('scheduled_start_time')
            ->first();

        $recent = AttendanceSession::where('student_id', $student->id)
            ->where('scheduled_start_time', '>=', now()->subDays(7))
            ->orderByDesc('scheduled_start_time')
            ->get();

        $risk = $student->latestRiskScore;

        return [
            // Khoi 0 (dau): den tin hieu risk
            'risk' => [
                'score' => $risk?->risk_score,
                'level' => $risk?->level,
            ],
            // 1. Lich hoc hom nay
            'today_schedule' => [
                'has_session' => $today !== null,
                'lesson' => $today?->lesson->title,
                'scheduled_at' => $today?->scheduled_start_time?->timezone('Asia/Ho_Chi_Minh')->format('H:i'),
            ],
            // 2. Trang thai tham gia
            'participation' => [
                'status' => $today?->attendance_status,
                'entered' => $today?->actual_start_time !== null,
            ],
            // 3. Thoi gian hoc that
            'study_time' => [
                'summary' => $today ? $this->attendance->focusSummary($today) : null,
                'effective_minutes' => $today?->effective_study_minutes,
            ],
            // 4. Ket qua buoi hoc
            'session_result' => [
                'completion_rate' => $today?->completion_rate,
            ],
            // 5. Canh bao bat thuong
            'alerts' => $this->alerts($student, $recent),
            // 6. Goi y can thiep
            'interventions' => $this->interventions($recent, $risk?->level),
        ];
    }

    /** SPEC §3.7 — cac rule canh bao. */
    private function alerts(Student $student, $recent): array
    {
        $alerts = [];
        $streakHigh = config('hoctoan.parent_alerts.absent_streak_high');

        // Vang N buoi lien tiep.
        $consecutiveAbsent = 0;
        foreach ($recent as $session) {
            if ($session->attendance_status === AttendanceSession::STATUS_ABSENT) {
                $consecutiveAbsent++;
            } else {
                break;
            }
        }
        if ($consecutiveAbsent >= $streakHigh) {
            $alerts[] = ['level' => 'danger', 'text' => "Vắng {$consecutiveAbsent} buổi liên tiếp"];
        }

        // Diem quiz giam 3 buoi lien tiep.
        $declineSessions = config('hoctoan.parent_alerts.quiz_decline_sessions');
        $scores = $student->quizAttempts()
            ->whereNotNull('submitted_at')->orderByDesc('submitted_at')
            ->limit($declineSessions)->pluck('score')->map(fn ($s) => (float) $s)->values();

        if ($scores->count() >= $declineSessions) {
            $declining = true;
            for ($i = 0; $i < $scores->count() - 1; $i++) {
                if ($scores[$i] >= $scores[$i + 1]) {
                    $declining = false;
                    break;
                }
            }
            if ($declining) {
                $alerts[] = ['level' => 'warn', 'text' => "Điểm quiz giảm {$declineSessions} buổi liên tiếp"];
            }
        }

        return $alerts;
    }

    private function interventions($recent, ?string $riskLevel): array
    {
        $out = [];

        if ($riskLevel === 'nguy_co_cao') {
            $out[] = 'Cần nhắc con học và theo dõi sát trong tuần này.';
        }

        $partialCount = $recent->where('attendance_status', 'partial')->count();
        if ($partialCount >= 2) {
            $out[] = 'Con hay học chưa đủ thời lượng — cân nhắc đổi khung giờ học.';
        }

        if (empty($out)) {
            $out[] = 'Con đang học ổn định. Tiếp tục động viên nhé.';
        }

        return $out;
    }
}
