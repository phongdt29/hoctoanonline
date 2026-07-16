<?php

namespace App\Jobs;

use App\Models\AttendanceSession;
use App\Models\ParentNotification;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ticket R1 — bao cao cuoi tuan gui phu huynh (in_app + email).
 * Scheduler goi weeklyOn(1, ...) (8h sang T2 VN).
 */
class SendWeeklyReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $studentId) {}

    public function handle(NotificationDispatcher $dispatcher): void
    {
        $student = Student::with('parents.user')->find($this->studentId);

        if (! $student || $student->parents->isEmpty()) {
            return;
        }

        $summary = $this->weekSummary($student);

        foreach ($student->parents as $parent) {
            $dispatcher->send(
                $parent,
                $student->id,
                ParentNotification::TYPE_WEEKLY_REPORT,
                "Báo cáo tuần của {$student->full_name}",
                $summary,
                ['in_app', 'email'],
            );
        }
    }

    private function weekSummary(Student $student): string
    {
        $since = now()->subDays(7);

        $sessions = AttendanceSession::where('student_id', $student->id)
            ->where('scheduled_start_time', '>=', $since)->get();

        $present = $sessions->where('attendance_status', 'present')->count();
        $absent = $sessions->where('attendance_status', 'absent')->count();
        $studyMinutes = $sessions->sum('effective_study_minutes');

        $avgScore = QuizAttempt::where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $since)
            ->avg('score');

        $risk = $student->latestRiskScore?->level ?? 'chưa có dữ liệu';

        return sprintf(
            'Tuần qua: %d buổi có mặt, %d buổi vắng, học thực %d phút. Điểm quiz trung bình: %s. Tình trạng: %s.',
            $present,
            $absent,
            $studyMinutes,
            $avgScore !== null ? number_format((float) $avgScore, 1) : '—',
            str_replace('_', ' ', $risk),
        );
    }
}
