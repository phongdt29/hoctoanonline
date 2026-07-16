<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\SchoolClass;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Ticket T1/T2 — giao vien: gradebook + cham bai. */
class TeacherController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    /** GET /api/v1/teacher/classes/{class}/gradebook */
    public function gradebook(Request $request, SchoolClass $class): JsonResponse
    {
        $this->authorizeTeacher($request, $class);

        // Eager-load tranh N+1: students + submissions.
        $class->load(['students', 'assignments.submissions']);

        $rows = $class->students->map(function ($student) use ($class) {
            $scores = $class->assignments->map(function ($assignment) use ($student) {
                $sub = $assignment->submissions->firstWhere('student_id', $student->id);

                return [
                    'assignment_id' => $assignment->id,
                    'submitted' => $sub !== null,
                    'score' => $sub?->score,
                ];
            });

            return [
                'student_id' => $student->id,
                'name' => $student->full_name,
                'scores' => $scores,
            ];
        });

        return response()->json(['data' => $rows, 'message' => 'OK']);
    }

    /** POST /api/v1/teacher/submissions/{submission}/grade */
    public function grade(Request $request, AssignmentSubmission $submission): JsonResponse
    {
        // Giao vien phai la chu lop chua bai nay.
        $class = $submission->assignment->schoolClass;
        $this->authorizeTeacher($request, $class);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'between:0,10'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $graded = $this->assignments->grade($submission, (float) $data['score'], $data['feedback'] ?? null);

        return response()->json([
            'data' => ['score' => (float) $graded->score, 'graded_at' => $graded->graded_at->toIso8601String()],
            'message' => 'Đã chấm bài. Phụ huynh đã được thông báo.',
        ]);
    }

    private function authorizeTeacher(Request $request, SchoolClass $class): void
    {
        abort_unless($class->teacher_id === $request->user()->id, 403, 'Đây không phải lớp của bạn.');
    }
}
