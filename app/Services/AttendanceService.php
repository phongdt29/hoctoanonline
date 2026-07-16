<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\StudentActivityLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ticket M2 + SPEC §3.5 — thoi gian hoc THAT + diem danh 3 trang thai.
 *
 * effective_study_time: chi tinh gap giua 2 event hop le <= idle_gap_minutes.
 * tab_inactive / idle > nguong -> KHONG tinh.
 *
 * 3 trang thai (khong phai 2):
 *   present : vao + effective >= present_ratio * chuan buoi + co nop quiz
 *   partial : effective < nguong HOAC bo quiz
 *   absent  : khong vao / gan nhu khong tuong tac
 *
 * Muc dich: phu huynh nhin "online 90 phut — hoc thuc 8 phut" thay vi chi "co mat".
 */
class AttendanceService
{
    /** Chuan thoi luong 1 buoi (phut) — dung de tinh nguong 70%. */
    private const SESSION_STANDARD_MINUTES = 45;

    /** Ghi batch event tu frontend (M1). Bulk insert, khong xu ly nang trong request. */
    public function recordEvents(AttendanceSession $session, array $events): int
    {
        $rows = [];

        foreach ($events as $event) {
            if (! in_array($event['event_type'] ?? '', self::validEventTypes(), true)) {
                continue;   // bo event khong hop le
            }

            $rows[] = [
                'session_id' => $session->id,
                'event_type' => $event['event_type'],
                'event_time' => Carbon::parse($event['event_time'] ?? now()),
                'metadata' => isset($event['metadata']) ? json_encode($event['metadata']) : null,
            ];
        }

        if ($rows !== []) {
            StudentActivityLog::insert($rows);
        }

        return count($rows);
    }

    /**
     * Tinh effective_study_time tu activity logs: cong cac gap <= idle_gap_minutes
     * giua 2 event hop le lien tiep. Gap lon hon (idle) KHONG tinh.
     */
    public function computeEffectiveMinutes(AttendanceSession $session): int
    {
        $gap = config('hoctoan.attendance.idle_gap_minutes');

        $events = $session->activityLogs()
            ->orderBy('event_time')
            ->get()
            ->filter(fn ($e) => $e->event_type !== StudentActivityLog::EVENT_TAB_INACTIVE)
            ->values();

        $effectiveSeconds = 0;

        for ($i = 1; $i < $events->count(); $i++) {
            $delta = $events[$i - 1]->event_time->diffInSeconds($events[$i]->event_time);

            // Chi cong gap "lien tuc" (<= nguong idle). Gap lon = da rong.
            if ($delta <= $gap * 60) {
                $effectiveSeconds += $delta;
            }
        }

        return (int) round($effectiveSeconds / 60);
    }

    /**
     * Chot trang thai diem danh cho 1 phien. Cap nhat effective_study_minutes,
     * idle_minutes, attendance_status, completion_rate.
     */
    public function finalize(AttendanceSession $session, bool $hasQuizSubmit): AttendanceSession
    {
        $effective = $this->computeEffectiveMinutes($session);
        $online = $session->onlineMinutes();
        $ratio = config('hoctoan.attendance.present_ratio');
        $threshold = self::SESSION_STANDARD_MINUTES * $ratio;

        $status = $this->determineStatus($session, $effective, $threshold, $hasQuizSubmit);

        $session->update([
            'effective_study_minutes' => $effective,
            'idle_minutes' => max(0, $online - $effective),
            'attendance_status' => $status,
            'completion_rate' => round(min(100, $effective / self::SESSION_STANDARD_MINUTES * 100), 2),
        ]);

        return $session->fresh();
    }

    private function determineStatus(
        AttendanceSession $session,
        int $effective,
        float $threshold,
        bool $hasQuizSubmit,
    ): string {
        $entered = $session->actual_start_time !== null;

        if (! $entered || $effective < 2) {
            return AttendanceSession::STATUS_ABSENT;
        }

        // present: du thoi luong + co nop quiz. Thieu 1 trong 2 -> partial.
        if ($effective >= $threshold && $hasQuizSubmit) {
            return AttendanceSession::STATUS_PRESENT;
        }

        return AttendanceSession::STATUS_PARTIAL;
    }

    /** Text cho phu huynh: "online X phut — hoc thuc Y phut — muc tap trung ...". */
    public function focusSummary(AttendanceSession $session): string
    {
        $online = $session->onlineMinutes();
        $effective = $session->effective_study_minutes;
        $ratio = $session->focusRatio();

        $level = match (true) {
            $ratio >= 0.7 => 'tốt',
            $ratio >= 0.4 => 'trung bình',
            default => 'thấp',
        };

        return "online {$online} phút — học thực {$effective} phút — mức tập trung {$level}";
    }

    public static function validEventTypes(): array
    {
        return array_merge(
            StudentActivityLog::ACTIVE_EVENTS,
            [StudentActivityLog::EVENT_TAB_INACTIVE],
        );
    }
}
