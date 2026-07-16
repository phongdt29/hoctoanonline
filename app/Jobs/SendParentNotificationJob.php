<?php

namespace App\Jobs;

use App\Models\ParentNotification;
use App\Models\Student;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ticket M3/M4 — gui thong bao toi TAT CA phu huynh da link voi hoc sinh.
 * Kenh in_app truoc (SPEC §8 P3: email/SMS/push la roadmap R1).
 */
class SendParentNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $studentId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $content,
    ) {}

    public function handle(): void
    {
        $student = Student::with('parents')->find($this->studentId);

        if (! $student) {
            return;
        }

        foreach ($student->parents as $parent) {
            ParentNotification::create([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'notification_type' => $this->type,
                'title' => $this->title,
                'content' => $this->content,
                'channel' => ParentNotification::CHANNEL_IN_APP,
                'sent_at' => now(),
            ]);
        }
    }
}
