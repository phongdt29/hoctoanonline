<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Soan bai — admin sua noi dung lesson (ly thuyet + bai tap), ho tro cong thuc toan.
 *
 * Cong thuc toan luu duoi dang LaTeX trong text ($...$ hoac \(...\)); MathJax o
 * layout base render khi hien thi. Noi dung van escape (e()) khi in -> khong XSS,
 * MathJax chi quet text node nen cong thuc van len dep.
 */
class LessonController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $lessons = Lesson::query()
            ->with(['module.curriculum.student'])
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.lessons', ['lessons' => $lessons, 'q' => $q]);
    }

    public function edit(Lesson $lesson): View
    {
        $lesson->load(['exercises' => fn ($q) => $q->orderBy('difficulty'), 'module.curriculum.student']);

        return view('admin.lesson-edit', ['lesson' => $lesson]);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:200'],
            'theory_content' => ['required', 'string', 'max:20000'],
            'exercises'      => ['array'],
            'exercises.*'    => ['nullable', 'string', 'max:5000'],
        ]);

        $lesson->update([
            'title'          => $data['title'],
            'theory_content' => $data['theory_content'],
        ]);

        // Cap nhat noi dung tung bai tap (chi field content — dap an giu nguyen).
        foreach ($data['exercises'] ?? [] as $exerciseId => $content) {
            if ($content === null || $content === '') {
                continue;
            }
            $lesson->exercises()->whereKey($exerciseId)->update(['content' => $content]);
        }

        return redirect()
            ->route('admin.lessons.edit', $lesson)
            ->with('status', 'Đã lưu bài học.');
    }
}
