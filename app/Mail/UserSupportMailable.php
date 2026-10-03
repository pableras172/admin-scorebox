<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserSupportMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $supportSubject,
        public string $supportMessage,
        public ?string $userName = null,
        public ?string $promoCode = null,
        public ?string $recipientEmail = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->supportSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-support',
            with: [
                'subject' => $this->supportSubject,
                'supportMessage' => $this->supportMessage,
                'userName' => $this->userName,
                'promoCode' => $this->promoCode,
                'recipientEmail' => $this->recipientEmail,
            ],
        );
    }
}
