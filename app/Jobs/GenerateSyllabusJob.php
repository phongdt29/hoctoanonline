<?php

namespace App\Jobs;

use App\Models\Syllabus;
use App\Services\SyllabusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sinh giao trinh mau bang AI o nen. Queue tries=3, backoff tang dan.
 * Neu ca 3 lan deu that bai -> failed() danh dau status=failed de admin thay va thu lai.
 */
class GenerateSyllabusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public readonly int $syllabusId) {}

    public function handle(SyllabusService $service): void
    {
        $syllabus = Syllabus::find($this->syllabusId);

        if (! $syllabus || $syllabus->status === Syllabus::STATUS_READY) {
            return;
        }

        $service->generate($syllabus);
    }

    /** Het luot thu -> danh dau that bai kem ly do de admin retry. */
    public function failed(\Throwable $e): void
    {
        Syllabus::whereKey($this->syllabusId)->update([
            'status' => Syllabus::STATUS_FAILED,
            'error'  => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
