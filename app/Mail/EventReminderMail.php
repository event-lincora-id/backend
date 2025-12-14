<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public User $user;
    public string $reminderType;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, User $user, string $reminderType = 'h1')
    {
        $this->event = $event;
        $this->user = $user;
        $this->reminderType = $reminderType; // 'h1' (1 day before) or 'h0' (1 hour before)
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->reminderType === 'h0'
            ? 'Reminder: ' . $this->event->title . ' dimulai 1 jam lagi!'
            : 'Reminder: ' . $this->event->title . ' Tomorrow';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.event-reminder',
            with: [
                'event' => $this->event,
                'user' => $this->user,
                'reminderType' => $this->reminderType,
                'eventDate' => $this->event->start_date->format('l, d F Y'),
                'eventTime' => $this->event->start_date->format('H:i'),
                'eventEndDate' => $this->event->end_date->format('l, d F Y'),
                'eventEndTime' => $this->event->end_date->format('H:i'),
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