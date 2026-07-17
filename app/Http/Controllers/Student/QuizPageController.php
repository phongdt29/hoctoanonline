<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Trang lam quiz cuoi buoi (host UI + quiz.js). Timer + cham do server (API). */
class QuizPageController extends Controller
{
    public function show(Quiz $quiz): View|RedirectResponse
    {
        $student = request()->user()->student;
        $lesson = $quiz->lesson;

        // Quiz phai thuoc lesson trong curriculum cua chinh hoc sinh, va lesson mo khoa.
        $owns = $lesson->module?->curriculum?->student_id === $student?->id;
        abort_unless($owns, 403);

        if (! $lesson->isAccessible()) {
            return redirect()->route('curriculum')
                ->with('status', 'Hãy mở khóa bài học trước khi làm quiz.');
        }

        return view('student.quiz', [
            'quiz' => $quiz,
            'lesson' => $lesson,
            'themeColor' => $student->favorite_color,
        ]);
    }
}
