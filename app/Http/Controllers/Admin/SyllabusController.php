<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSyllabusJob;
use App\Models\Student;
use App\Models\Syllabus;
use App\Services\AssignSyllabusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lên giáo trình bằng AI — thu vien giao trinh mau dung chung.
 *
 * Tao giao trinh -> dispatch GenerateSyllabusJob (nen) -> admin xem tien do (auto-refresh).
 * Sinh day du (khung + ly thuyet + bai tap) nen chay o queue, khong block request.
 */
class SyllabusController extends Controller
{
    public function index(): View
    {
        return view('admin.syllabi', [
            'syllabi' => Syllabus::latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:200'],
            'grade'            => ['required', 'integer', 'min:1', 'max:12'],
            'topic'            => ['nullable', 'string', 'max:200'],
            'goal'             => ['nullable', 'string', 'max:2000'],
            'planned_sessions' => ['nullable', 'integer', 'min:0', 'max:40'],
        ]);

        $syllabus = Syllabus::create([
            'title'            => $data['title'],
            'grade'            => $data['grade'],
            'topic'            => $data['topic'] ?? null,
            'goal'             => $data['goal'] ?? null,
            'planned_sessions' => $data['planned_sessions'] ?? 0,
            'status'           => Syllabus::STATUS_GENERATING,
            'created_by'       => $request->user()->id,
        ]);

        GenerateSyllabusJob::dispatch($syllabus->id);

        return redirect()
            ->route('admin.syllabi.show', $syllabus)
            ->with('status', 'Đang tạo giáo trình bằng AI — trang sẽ tự cập nhật khi xong.');
    }

    public function show(Syllabus $syllabus): View
    {
        // Danh sach hoc sinh de gan (uu tien cung khoi lop len dau).
        $students = $syllabus->isReady()
            ? Student::orderByRaw('grade = ? DESC', [$syllabus->grade])
                ->orderBy('grade')->orderBy('full_name')
                ->get(['id', 'full_name', 'grade'])
            : collect();

        return view('admin.syllabus', ['s' => $syllabus, 'students' => $students]);
    }

    /** Gan giao trinh cho 1 hoc sinh (clone thanh lo trinh that). */
    public function assign(Request $request, Syllabus $syllabus, AssignSyllabusService $service): RedirectResponse
    {
        if (! $syllabus->isReady()) {
            return back()->with('error', 'Giáo trình chưa sẵn sàng để gán.');
        }

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $student = Student::findOrFail($data['student_id']);
        $curriculum = $service->assign($syllabus, $student);

        return back()->with('status',
            "Đã gán giáo trình cho {$student->full_name} — {$curriculum->modules()->count()} chương. "
            .'Học sinh có thể bắt đầu học ngay.');
    }

    /** Tao lai khi that bai (hoac muon sinh lai). */
    public function retry(Syllabus $syllabus): RedirectResponse
    {
        $syllabus->update(['status' => Syllabus::STATUS_GENERATING, 'error' => null]);
        GenerateSyllabusJob::dispatch($syllabus->id);

        return redirect()
            ->route('admin.syllabi.show', $syllabus)
            ->with('status', 'Đang tạo lại giáo trình…');
    }

    public function destroy(Syllabus $syllabus): RedirectResponse
    {
        $title = $syllabus->title;
        $syllabus->delete();

        return redirect()
            ->route('admin.syllabi')
            ->with('status', "Đã xoá giáo trình \"{$title}\".");
    }
}
