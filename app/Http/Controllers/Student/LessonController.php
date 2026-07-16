<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\View\View;

/** Ticket L1 — xem lesson. Policy chan lesson locked / khong phai cua minh. */
class LessonController extends Controller
{
    public function show(Lesson $lesson): View
    {
        // LessonPolicy::view — 403 neu locked hoac khong thuoc curriculum cua minh.
        $this->authorize('view', $lesson);

        $lesson->load('exercises', 'quiz', 'module');

        // Danh dau dang hoc (neu vua mo khoa).
        if ($lesson->status === Lesson::STATUS_UNLOCKED) {
            $lesson->update(['status' => Lesson::STATUS_IN_PROGRESS]);
        }

        return view('student.lesson', [
            'lesson' => $lesson,
            'themeColor' => request()->user()->student?->favorite_color,
        ]);
    }
}
