<?php

namespace App\Jobs;

use App\Models\StudentClassification;
use App\Services\CurriculumService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ticket C5 — sinh giao trinh. Buoc cuoi cua chuoi tu dong
 * Grade -> Classify -> Generate. Queue tries=3, backoff=[30,120,300].
 */
class GenerateCurriculumJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public readonly int $classificationId) {}

    public function handle(CurriculumService $curriculum): void
    {
        $classification = StudentClassification::with('student', 'topicAbilities')
            ->find($this->classificationId);

        if (! $classification) {
            return;
        }

        // Idempotent: da sinh giao trinh cho phan loai nay roi thi thoi.
        if ($classification->curricula()->exists()) {
            return;
        }

        $curriculum->generate($classification);
    }
}
