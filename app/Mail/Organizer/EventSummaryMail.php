<?php

namespace App\Mail\Organizer;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public User $organizer;
    public array $statistics;
    public ?string $feedbackSummary;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, User $organizer, array $statistics, ?string $feedbackSummary = null)
    {
        $this->event = $event;
        $this->organizer = $organizer;
        $this->statistics = $statistics;
        $this->feedbackSummary = $feedbackSummary;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event Summary - ' . $this->event->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.organizer.event-summary',
            with: [
                'event' => $this->event,
                'organizer' => $this->organizer,
                'statistics' => $this->statistics,
                'feedbackSummary' => $this->feedbackSummary,
            ]
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
