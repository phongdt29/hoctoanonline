<?php

namespace App\Jobs;

use App\Models\AttendanceSession;
use App\Models\StudentActivityLog;
use App\Services\AttendanceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ticket M3 + SPEC §3.5 — chot diem danh + flow vang mat.
 * Scheduler goi moi 5 phut (routes/console.php).
 *
 * Flow vang (nguong tu config):
 *   T+late_after_min chua vao        -> late
 *   T+absent_pending_after_min       -> absent_pending
 *   het khung gio, khong active du   -> chot absent + sinh thong bao/lesson bu
 */
class CloseAttendanceSessionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(AttendanceService $attendance): void
    {
        $late = config('hoctoan.attendance.late_after_min');
        $pending = config('hoctoan.attendance.absent_pending_after_min');

        // Cac phien con "dang cho" (chua chot).
        $sessions = AttendanceSession::whereIn('attendance_status', [
            AttendanceSession::STATUS_ABSENT_PENDING,
            AttendanceSession::STATUS_LATE,
        ])->get();

        foreach ($sessions as $session) {
            $minsSinceScheduled = $session->scheduled_start_time->diffInMinutes(now(), false);

            $hasEntered = $session->actual_start_time !== null;

            if ($hasEntered) {
                // Da vao -> chot theo effective time + co quiz hay khong.
                $hasQuiz = StudentActivityLog::where('session_id', $session->id)
                    ->where('event_type', 'quiz_submit')->exists();

                if (! $session->actual_end_time) {
                    $session->update(['actual_end_time' => now()]);
                }

                $attendance->finalize($session, $hasQuiz);
                $this->afterAbsent($session);

                continue;
            }

            // Chua vao -> chuyen trang thai theo moc thoi gian.
            if ($minsSinceScheduled >= $pending * 2) {
                // Het khung gio (uoc luong 2x moc pending) -> chot absent.
                $session->update(['attendance_status' => AttendanceSession::STATUS_ABSENT]);
                $this->afterAbsent($session);
            } elseif ($minsSinceScheduled >= $pending) {
                $session->update(['attendance_status' => AttendanceSession::STATUS_ABSENT_PENDING]);
            } elseif ($minsSinceScheduled >= $late) {
                $session->update(['attendance_status' => AttendanceSession::STATUS_LATE]);
            }
        }
    }

    /** Khi chot absent: thong bao phu huynh + cap nhat risk (SPEC §3.5). */
    private function afterAbsent(AttendanceSession $session): void
    {
        if ($session->attendance_status !== AttendanceSession::STATUS_ABSENT) {
            return;
        }

        SendParentNotificationJob::dispatch(
            $session->student_id,
            \App\Models\ParentNotification::TYPE_ABSENT,
            'Con vắng buổi học',
            'Con chưa tham gia buổi học theo lịch. Nhắc con vào học lại nhé.',
        );

        ComputeRiskScoreJob::dispatch($session->student_id);
    }
}
