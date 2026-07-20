<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\SchoolClass;
use App\Services\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Ticket T1/T2 — trang giao vien: lop, giao bai, cham bai (gradebook). */
class TeacherController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    /** GET /teacher/classes — danh sach lop cua giao vien. */
    public function classes(Request $request): View
    {
        $classes = SchoolClass::where('teacher_id', $request->user()->id)
            ->withCount(['students', 'assignments'])
            ->orderBy('name')
            ->get();

        return view('teacher.classes', ['classes' => $classes]);
    }

    /** GET /teacher/classes/{class} — chi tiet lop: hoc sinh + bai tap. */
    public function show(Request $request, SchoolClass $class): View
    {
        $this->authorizeOwner($request, $class);

        $class->load([
            'students:id,full_name,grade,points_balance',
            'assignments' => fn ($q) => $q->withCount('submissions')->latest('due_at'),
        ]);

        return view('teacher.class', ['class' => $class]);
    }

    /** POST /teacher/classes/{class}/assignments — giao bai moi. */
    public function storeAssignment(Request $request, SchoolClass $class): RedirectResponse
    {
        $this->authorizeOwner($request, $class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:5000'],
            'due_at' => ['required', 'date', 'after:now'],
        ], [], [
            'title' => 'tiêu đề', 'content' => 'nội dung', 'due_at' => 'hạn nộp',
        ]);

        $class->assignments()->create($data);

        return back()->with('status', 'Đã giao bài tập cho lớp.');
    }

    /** GET /teacher/assignments/{assignment} — danh sach bai nop de cham. */
    public function submissions(Request $request, Assignment $assignment): View
    {
        $class = $assignment->schoolClass;
        $this->authorizeOwner($request, $class);

        // Ghep tung hoc sinh trong lop voi bai nop (neu co) — thay ai chua nop.
        $class->load('students:id,full_name');
        $submissions = $assignment->submissions()->get()->keyBy('student_id');

        return view('teacher.assignment', [
            'assignment' => $assignment,
            'class' => $class,
            'submissions' => $submissions,
        ]);
    }

    /** POST /teacher/submissions/{submission}/grade — cham bai (web). */
    public function grade(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        $class = $submission->assignment->schoolClass;
        $this->authorizeOwner($request, $class);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'between:0,10'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ], [], ['score' => 'điểm']);

        $this->assignments->grade($submission, (float) $data['score'], $data['feedback'] ?? null);

        return back()->with('status', 'Đã chấm bài. Phụ huynh đã được thông báo.');
    }

    private function authorizeOwner(Request $request, SchoolClass $class): void
    {
        abort_unless($class->teacher_id === $request->user()->id, 403, 'Đây không phải lớp của bạn.');
    }
}
