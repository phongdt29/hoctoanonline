<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentDashboardService;
use Illuminate\View\View;

/** Ticket L4 — trang dashboard hoc sinh. */
class DashboardController extends Controller
{
    public function __construct(private readonly StudentDashboardService $dashboard) {}

    public function show(StudentDashboardService $dashboard): View
    {
        $student = request()->user()->student;

        $data = $this->dashboard->build($student);

        $lessons = $student->activeCurriculum?->lessons ?? collect();

        return view('student.dashboard', [
            'student' => $student,
            'd' => $data,
            'lessons' => $lessons,
            'themeColor' => $student->favorite_color,
        ]);
    }
}
