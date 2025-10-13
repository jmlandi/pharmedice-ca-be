<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;
    public string $userName;
    public string $expirationTime;

    /**
     * Create a new message instance.
     */
    public function __construct(string $resetUrl, string $userName, int $expirationMinutes = 60)
    {
        $this->resetUrl = $resetUrl;
        $this->userName = $userName;
        $this->expirationTime = $expirationMinutes . ' minutos';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔐 Recuperação de Senha - Pharmedice',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.password-reset',
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
