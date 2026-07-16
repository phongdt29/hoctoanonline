<?php

namespace App\Services\Notification;

use App\Mail\ParentNotificationMail;
use App\Models\ParentAccount;
use App\Models\ParentNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Ticket R1 — kenh email. Gui mail + ghi lai lich su (channel=email).
 * Mail queue (ShouldQueue) nen khong chan.
 */
class EmailChannel implements NotificationChannel
{
    public function send(ParentAccount $parent, int $studentId, string $type, string $title, string $content): void
    {
        $email = $parent->user?->email;

        // Ghi lich su du gui hay khong (audit).
        ParentNotification::create([
            'parent_id' => $parent->id,
            'student_id' => $studentId,
            'notification_type' => $type,
            'title' => $title,
            'content' => $content,
            'channel' => ParentNotification::CHANNEL_EMAIL,
            'sent_at' => now(),
        ]);

        if ($email) {
            Mail::to($email)->send(new ParentNotificationMail($title, $content, $parent->full_name));
        }
    }

    public function name(): string
    {
        return ParentNotification::CHANNEL_EMAIL;
    }
}
