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

    /**
     * POST /assessment/skip — BO QUA bai test, sinh lo trinh demo (khong AI).
     * CHI dev/test (route chi dang ky o local). Giup kiem thu cac tinh nang khac.
     */
    public function skip(\App\Services\DemoCurriculumService $demo): RedirectResponse
    {
        $student = request()->user()->student;

        $demo->generate($student);

        return redirect()->route('dashboard')
            ->with('status', 'Đã tạo lộ trình demo. Bạn có thể thử học, làm quiz, hỏi gia sư ngay.');
    }

    /** GET /assessment/{assessment}/result — trang ket qua. */
    public function result(Assessment $assessment): View|RedirectResponse
    {
        $student = request()->user()->student;

        abort_unless($assessment->student_id === $student?->id, 403);

        $assessment->load('questions', 'classification.topicAbilities');

        // Chuoi job chay qua queue: submit -> graded -> classified -> curriculum.
        // Trang ket qua CAN classification (final_level, topic_abilities). Neu chua co
        // (job Classify chua chay xong) -> hien "dang xu ly", KHONG render voi null.
        // Truoc day chi check status='graded' -> co cua so graded-nhung-chua-classified
        // gay loi "final_level on null".
        if ($assessment->status !== Assessment::STATUS_GRADED || $assessment->classification === null) {
            return view('student.assessment-processing', [
                'assessment' => $assessment,
                'themeColor' => $student->favorite_color,
            ]);
        }

        return view('student.assessment-result', [
            'assessment' => $assessment,
            'classification' => $assessment->classification,
            'themeColor' => $student->favorite_color,
        ]);
    }
}
