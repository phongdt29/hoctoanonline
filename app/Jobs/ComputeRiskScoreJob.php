<?php

namespace App\Jobs;

use App\Models\Student;
use App\Services\RiskScoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Ticket M4 — tinh lai risk score (goi khi chot absent hoac scheduler hang ngay). */
class ComputeRiskScoreJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $studentId) {}

    public function handle(RiskScoreService $risk): void
    {
        $student = Student::find($this->studentId);

        if ($student) {
            $risk->compute($student);
        }
    }
}
