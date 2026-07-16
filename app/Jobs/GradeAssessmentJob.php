<?php

namespace App\Jobs;

use App\Models\Assessment;
use App\Models\Student;
use App\Services\GradingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ticket C4 — cham bai (co goi AI cho tu luan) chay trong Job, KHONG trong request
 * (CLAUDE.md #4). Cham xong -> dispatch ClassifyStudentJob (chuoi tu dong).
 */
class GradeAssessmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int,int> backoff giay giua cac lan retry (SPEC §1) */
    public array $backoff = [30, 120, 300];

    public function __construct(public readonly int $assessmentId) {}

    public function handle(GradingService $grading): void
    {
        $assessment = Assessment::with('questions', 'student')->find($this->assessmentId);

        if (! $assessment || $assessment->status === Assessment::STATUS_GRADED) {
            return;   // idempotent — retry khong cham lai
        }

        $grading->grade($assessment);

        $assessment->student->update(['status' => Student::STATUS_ASSESSED]);

        ClassifyStudentJob::dispatch($assessment->id);
    }
}
