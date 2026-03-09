<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $leadMessage
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: 'New Contact Form Submission - Lead Notification',
        );

        // Set reply-to with proper email validation
        // Clean the name to remove any HTML entities or special characters that might cause RFC 2822 issues
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $cleanName = html_entity_decode(strip_tags($this->name), ENT_QUOTES, 'UTF-8');
            // Use the method signature: replyTo(string $address, string|null $name = null)
            $envelope->replyTo($this->email, $cleanName);
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-notification',
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
