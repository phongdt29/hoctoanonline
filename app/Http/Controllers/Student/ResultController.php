<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trang "Kết quả học tập" — hoc sinh xem minh da hoc toi dau + hoc tot khong:
 * tien do tong, diem quiz qua tung buoi, nang luc theo chu de, lich su quiz.
 */
class ResultController extends Controller
{
    public function show(Request $request): View
    {
        $student = $request->user()->student;

        $curriculum = $student?->activeCurriculum()->with('modules.lessons')->first();
        $lessons = $curriculum ? $curriculum->modules->flatMap->lessons : collect();

        $done  = $lessons->where('status', Lesson::STATUS_COMPLETED)->count();
        $total = $lessons->count();
        $next  = $lessons->firstWhere('status', 'in_progress') ?? $lessons->firstWhere('status', 'unlocked');

        // Lich su quiz da nop (kem ten bai).
        $attempts = QuizAttempt::where('student_id', $student?->id)
            ->whereNotNull('submitted_at')
            ->with('quiz.lesson')
            ->orderBy('submitted_at')
            ->get();

        $classification = $student?->latestClassification()->with('topicAbilities')->first();

        return view('student.results', [
            'student'        => $student,
            'curriculum'     => $curriculum,
            'done'           => $done,
            'total'          => $total,
            'percent'        => $total > 0 ? (int) round($done / $total * 100) : 0,
            'next'           => $next,
            'attempts'       => $attempts,
            'avgScore'       => $attempts->isNotEmpty() ? round((float) $attempts->avg('score'), 1) : null,
            'bestScore'      => $attempts->isNotEmpty() ? (float) $attempts->max('score') : null,
            'abilities'      => $classification?->topicAbilities ?? collect(),
            'weakTopics'     => $classification?->weak_topics ?? [],
        ]);
    }
}
