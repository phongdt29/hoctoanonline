<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ticket A2 — mail dat lai mat khau.
 *
 * ShouldQueue: gui mail la I/O cham, khong duoc chan request (CLAUDE.md #4).
 * Local chay queue driver=database, production=redis — code khong quan tam driver.
 */
class ResetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $resetUrl,
        public readonly string $recipientName,
        public readonly int $ttlMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Đặt lại mật khẩu hoctoanonline');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reset-password');
    }
}
