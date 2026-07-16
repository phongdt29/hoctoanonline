<?php

namespace App\Services\Notification;

use App\Models\ParentAccount;

/**
 * Ticket R1 (SPEC §8) — interface kenh thong bao.
 * Moi kenh (in_app, email, sms, push) implement interface nay.
 * Code goi khong biet kenh nao -> them kenh moi khong sua logic.
 */
interface NotificationChannel
{
    public function send(ParentAccount $parent, int $studentId, string $type, string $title, string $content): void;

    /** Ten kenh (khop enum parent_notifications.channel). */
    public function name(): string;
}
