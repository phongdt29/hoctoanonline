<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ticket T2 — hoc sinh nhan & nop bai (mat xich 45%).
 */
class StudentAssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    /** GET /api/v1/student/assignments — bai cua cac lop hoc sinh dang tham gia. */
    public function index(Request $request): JsonResponse
    {
        $student = $this->student($request);

        $classIds = $student->classes()->pluck('classes.id');

        $assignments = Assignment::whereIn('class_id', $classIds)
            ->with(['submissions' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderByDesc('due_at')
            ->get()
            ->map(function (Assignment $a) {
                $submission = $a->submissions->first();

                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'content' => $a->content,
                    'due_at' => $a->due_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                    'overdue' => $a->isOverdue(),
                    'submitted' => $submission !== null,
                    'score' => $submission?->score,
                    'graded' => $submission?->isGraded() ?? false,
                ];
            });

        return response()->json(['data' => $assignments, 'message' => 'OK']);
    }

    /** POST /api/v1/student/assignments/{assignment}/submit */
    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        $student = $this->student($request);

        // Hoc sinh phai thuoc lop cua assignment.
        abort_unless(
            $student->classes()->whereKey($assignment->class_id)->exists(),
            403,
            'Bài này không thuộc lớp của bạn.',
        );

        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'file_url' => ['nullable', 'string', 'max:500'],
        ]);

        abort_if(
            empty($data['content']) && empty($data['file_url']),
            422,
            'Cần nhập nội dung hoặc đính kèm file.',
        );

        $submission = $this->assignments->submit($assignment, $student, $data);

        return response()->json([
            'data' => ['submission_id' => $submission->id, 'submitted_at' => $submission->submitted_at->toIso8601String()],
            'message' => 'Đã nộp bài.',
        ]);
    }

    private function student(Request $request): \App\Models\Student
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);

        return $student;
    }
}
