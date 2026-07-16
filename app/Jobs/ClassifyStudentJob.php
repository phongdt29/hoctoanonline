<?php

namespace App\Jobs;

use App\Models\Assessment;
use App\Services\ClassificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ticket C4 — phan loai 2 tang. Xong -> dispatch GenerateCurriculumJob.
 * Queue tries=3, backoff=[30,120,300] (SPEC §1).
 */
class ClassifyStudentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public readonly int $assessmentId) {}

    public function handle(ClassificationService $classifier): void
    {
        $assessment = Assessment::with('questions', 'student')->find($this->assessmentId);

        if (! $assessment || $assessment->status !== Assessment::STATUS_GRADED) {
            return;
        }

        // Idempotent: da phan loai bai nay roi thi thoi (retry khong tao trung).
        if ($assessment->classification()->exists()) {
            return;
        }

        $classification = $classifier->classify($assessment);

        GenerateCurriculumJob::dispatch($classification->id);
    }
}
