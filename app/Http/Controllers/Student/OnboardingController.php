<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\OnboardingRequest;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Ticket C2 — onboarding hoc sinh. */
class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboarding,
        private readonly AuditService $audit,
    ) {}

    public function show(): View
    {
        $student = request()->user()->student;

        // Da onboard roi thi khong quay lai form -> di tiep sang assessment.
        if ($student && $student->hasReachedStatus(Student::STATUS_ONBOARDED)) {
            return view('student.onboarding', ['student' => $student, 'done' => true]);
        }

        return view('student.onboarding', ['student' => $student, 'done' => false]);
    }

    public function store(OnboardingRequest $request): RedirectResponse
    {
        $student = $request->user()->student;

        $this->onboarding->complete($student, $request->validated());

        $this->audit->log('onboarding_completed', $request->user(), 'students', $student->id, [
            'grade' => $student->grade,
        ]);

        return redirect()->route('assessment')
            ->with('status', 'Hồ sơ đã lưu. Giờ làm bài kiểm tra đầu vào để A.I xây lộ trình cho bạn nhé.');
    }
}
