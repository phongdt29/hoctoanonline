<?php

namespace App\Jobs;

use App\Models\Student;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ticket M3/M4/R1 — gui thong bao toi TAT CA phu huynh da link voi hoc sinh,
 * qua cac kenh chi dinh (mac dinh in_app; R1 them email).
 */
class SendParentNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param string[] $channels */
    public function __construct(
        public readonly int $studentId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $content,
        public readonly array $channels = ['in_app'],
    ) {}

    public function handle(NotificationDispatcher $dispatcher): void
    {
        $student = Student::with('parents.user')->find($this->studentId);

        if (! $student) {
            return;
        }

        foreach ($student->parents as $parent) {
            $dispatcher->send(
                $parent,
                $student->id,
                $this->type,
                $this->title,
                $this->content,
                $this->channels,
            );
        }
    }
}
