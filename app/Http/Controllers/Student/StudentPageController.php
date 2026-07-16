<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Cac trang Blade phu cua hoc sinh: solver, tutor, profile (host UI + JS). */
class StudentPageController extends Controller
{
    public function solver(): View
    {
        return view('student.solver', [
            'themeColor' => request()->user()->student?->favorite_color,
        ]);
    }

    public function tutor(): View
    {
        return view('student.tutor', [
            'themeColor' => request()->user()->student?->favorite_color,
        ]);
    }

    public function profile(): View
    {
        return view('student.profile', [
            'student' => request()->user()->student,
            'themeColor' => request()->user()->student?->favorite_color,
        ]);
    }
}
