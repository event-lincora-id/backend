<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use App\Models\EventParticipant;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable
{
    use SerializesModels;  // Removed Queueable to send emails immediately

    public Event $event;
    public User $user;
    public EventParticipant $participant;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, User $user, EventParticipant $participant)
    {
        $this->event = $event;
        $this->user = $user;
        $this->participant = $participant;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Successful - ' . $this->event->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-success',
            with: [
                'event' => $this->event,
                'user' => $this->user,
                'participant' => $this->participant,
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
