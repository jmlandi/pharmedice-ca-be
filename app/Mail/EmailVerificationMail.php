<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationUrl;
    public string $userName;
    public string $expirationTime;

    /**
     * Create a new message instance.
     */
    public function __construct(string $verificationUrl, string $userName, int $expirationMinutes = 60)
    {
        $this->verificationUrl = $verificationUrl;
        $this->userName = $userName;
        $this->expirationTime = $expirationMinutes . ' minutos';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✉️ Confirme seu Email - Pharmedice',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.email-verification',
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
