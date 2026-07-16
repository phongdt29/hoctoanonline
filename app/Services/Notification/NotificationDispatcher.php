<?php

namespace App\Services\Notification;

use App\Models\ParentAccount;
use InvalidArgumentException;

/**
 * Ticket R1 — gui thong bao qua 1 hoac nhieu kenh.
 * Roadmap: sms/push them adapter, khong sua code goi.
 */
class NotificationDispatcher
{
    public function __construct(
        private readonly InAppChannel $inApp,
        private readonly EmailChannel $email,
    ) {}

    /**
     * @param  string[]  $channels  ['in_app'], ['in_app','email'], ...
     */
    public function send(
        ParentAccount $parent,
        int $studentId,
        string $type,
        string $title,
        string $content,
        array $channels = ['in_app'],
    ): void {
        foreach ($channels as $channelName) {
            $this->channel($channelName)->send($parent, $studentId, $type, $title, $content);
        }
    }

    private function channel(string $name): NotificationChannel
    {
        return match ($name) {
            'in_app' => $this->inApp,
            'email' => $this->email,
            default => throw new InvalidArgumentException("Kenh khong ho tro: {$name}"),
        };
    }
}
