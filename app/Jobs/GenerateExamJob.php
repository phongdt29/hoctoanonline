<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Sinh de thi trac nghiem bang AI o nen. tries=3; het luot -> danh dau failed. */
class GenerateExamJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [20, 60, 180];

    public function __construct(public readonly int $examId) {}

    public function handle(ExamService $service): void
    {
        $exam = Exam::find($this->examId);

        if (! $exam || $exam->status === Exam::STATUS_READY) {
            return;
        }

        $service->generate($exam);
    }

    public function failed(\Throwable $e): void
    {
        Exam::whereKey($this->examId)->update([
            'status' => Exam::STATUS_FAILED,
            'error'  => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
