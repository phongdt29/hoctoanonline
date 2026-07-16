<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Lesson;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ticket M1 — nhan batch event tu activity-tracker.js (moi 15s / sendBeacon).
 * Bulk insert, KHONG xu ly nang trong request (tinh toan de M2/M3 lam sau).
 */
class ActivityController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    /** POST /api/v1/activity/events */
    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);

        $data = $request->validate([
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'events' => ['required', 'array', 'max:200'],
            'events.*.event_type' => ['required', 'string'],
            'events.*.event_time' => ['nullable', 'date'],
            'events.*.metadata' => ['nullable', 'array'],
        ]);

        $lesson = Lesson::findOrFail($data['lesson_id']);

        // Lesson phai thuoc curriculum cua chinh hoc sinh.
        abort_unless(
            $lesson->module?->curriculum?->student_id === $student->id,
            403,
        );

        $session = $this->todaySession($student->id, $lesson);

        $count = $this->attendance->recordEvents($session, $data['events']);

        return response()->json([
            'data' => ['session_id' => $session->id, 'recorded' => $count],
            'message' => 'OK',
        ]);
    }

    /** Get-or-create phien hoc hom nay cho lesson (1 phien/lesson/ngay). */
    private function todaySession(int $studentId, Lesson $lesson): AttendanceSession
    {
        $session = AttendanceSession::where('student_id', $studentId)
            ->where('lesson_id', $lesson->id)
            ->whereDate('scheduled_start_time', today())
            ->first();

        if ($session) {
            // Danh dau bat dau thuc te neu chua co.
            if ($session->actual_start_time === null) {
                $session->update(['actual_start_time' => now()]);
            }

            return $session;
        }

        return AttendanceSession::create([
            'student_id' => $studentId,
            'lesson_id' => $lesson->id,
            'scheduled_start_time' => now(),
            'actual_start_time' => now(),
            'attendance_status' => AttendanceSession::STATUS_ABSENT_PENDING,
        ]);
    }
}
