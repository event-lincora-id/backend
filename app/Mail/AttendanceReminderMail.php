<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use App\Models\EventParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public User $user;
    public EventParticipant $participant;
    public bool $isFinalReminder;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, User $user, EventParticipant $participant, bool $isFinalReminder = false)
    {
        $this->event = $event;
        $this->user = $user;
        $this->participant = $participant;
        $this->isFinalReminder = $isFinalReminder;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isFinalReminder
            ? 'Final Attendance Reminder - ' . $this->event->title
            : 'Attendance Reminder - ' . $this->event->title;

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
            view: 'emails.attendance-reminder',
            with: [
                'event' => $this->event,
                'user' => $this->user,
                'participant' => $this->participant,
                'isFinalReminder' => $this->isFinalReminder,
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
