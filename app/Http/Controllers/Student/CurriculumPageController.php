<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Ticket C6 — man hinh lo trinh (Mastery Grid theo 4 phase). */
class CurriculumPageController extends Controller
{
    public function show(): View
    {
        $student = request()->user()->student;

        $curriculum = $student?->activeCurriculum()
            ->with(['modules.lessons' => fn ($q) => $q->orderBy('lesson_order')])
            ->first();

        return view('student.curriculum', [
            'student' => $student,
            'curriculum' => $curriculum,
            'themeColor' => $student?->favorite_color,
        ]);
    }
}
