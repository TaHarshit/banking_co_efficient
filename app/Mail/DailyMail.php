<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $dynamicSubject;
    public string $dynamicView;
    public mixed $data;

    /**
     * Create a new message instance.
     */
    public function __construct($dynamicSubject, $dynamicView, $data)
    {
        $this->dynamicSubject = $dynamicSubject;
        $this->dynamicView = $dynamicView;
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->dynamicSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->dynamicView,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
