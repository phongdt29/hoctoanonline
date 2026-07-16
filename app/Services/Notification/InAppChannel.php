<?php

namespace App\Services\Notification;

use App\Models\ParentAccount;
use App\Models\ParentNotification;

/** Ticket R1 — kenh in-app: ghi vao parent_notifications. */
class InAppChannel implements NotificationChannel
{
    public function send(ParentAccount $parent, int $studentId, string $type, string $title, string $content): void
    {
        ParentNotification::create([
            'parent_id' => $parent->id,
            'student_id' => $studentId,
            'notification_type' => $type,
            'title' => $title,
            'content' => $content,
            'channel' => ParentNotification::CHANNEL_IN_APP,
            'sent_at' => now(),
        ]);
    }

    public function name(): string
    {
        return ParentNotification::CHANNEL_IN_APP;
    }
}
