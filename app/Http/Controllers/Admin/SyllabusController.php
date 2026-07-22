<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSyllabusJob;
use App\Models\Syllabus;
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
        return view('admin.syllabus', ['s' => $syllabus]);
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
