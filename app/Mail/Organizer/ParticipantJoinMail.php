<?php

namespace App\Mail\Organizer;

use App\Models\Event;
use App\Models\User;
use App\Models\EventParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParticipantJoinMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public User $organizer;
    public EventParticipant $participant;
    public ?float $totalRevenue;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, User $organizer, EventParticipant $participant, ?float $totalRevenue = null)
    {
        $this->event = $event;
        $this->organizer = $organizer;
        $this->participant = $participant;
        $this->totalRevenue = $totalRevenue;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Registration - ' . $this->event->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.organizer.participant-join',
            with: [
                'event' => $this->event,
                'organizer' => $this->organizer,
                'participant' => $this->participant,
                'totalRevenue' => $this->totalRevenue,
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
