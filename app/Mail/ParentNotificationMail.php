<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Ticket R1 — mail thong bao phu huynh. */
class ParentNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $notifTitle,
        public readonly string $notifContent,
        public readonly string $parentName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notifTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.parent-notification');
    }
}
