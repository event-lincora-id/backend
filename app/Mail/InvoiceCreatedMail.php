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

class InvoiceCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public User $user;
    public EventParticipant $participant;
    public string $invoiceUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, User $user, EventParticipant $participant, string $invoiceUrl)
    {
        $this->event = $event;
        $this->user = $user;
        $this->participant = $participant;
        $this->invoiceUrl = $invoiceUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Required - ' . $this->event->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-created',
            with: [
                'event' => $this->event,
                'user' => $this->user,
                'participant' => $this->participant,
                'invoiceUrl' => $this->invoiceUrl,
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
