<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Ticket C6 — trang Blade cho luong assessment (lam bai + ket qua).
 * Du lieu de/nop di qua API (routes/api.php); trang nay chi host UI + assessment.js.
 */
class AssessmentPageController extends Controller
{
    /** GET /assessment — trang lam bai. */
    public function show(): View|RedirectResponse
    {
        $student = request()->user()->student;

        // Da qua buoc assessed -> khong lam lai, xem ket qua moi nhat.
        if ($student && $student->hasReachedStatus(Student::STATUS_ASSESSED)) {
            $latest = $student->assessments()->where('status', Assessment::STATUS_GRADED)->latest()->first();

            if ($latest) {
                return redirect()->route('assessment.result', $latest);
            }
        }

        return view('student.assessment', [
            'themeColor' => $student?->favorite_color,
        ]);
    }

    /** GET /assessment/{assessment}/result — trang ket qua. */
    public function result(Assessment $assessment): View|RedirectResponse
    {
        $student = request()->user()->student;

        abort_unless($assessment->student_id === $student?->id, 403);

        // Chua cham xong (chuoi job dang chay) -> hien trang "dang xu ly".
        if ($assessment->status !== Assessment::STATUS_GRADED) {
            return view('student.assessment-processing', [
                'assessment' => $assessment,
                'themeColor' => $student->favorite_color,
            ]);
        }

        $assessment->load('questions', 'classification.topicAbilities');

        return view('student.assessment-result', [
            'assessment' => $assessment,
            'classification' => $assessment->classification,
            'themeColor' => $student->favorite_color,
        ]);
    }
}
