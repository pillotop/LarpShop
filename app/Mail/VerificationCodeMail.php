<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerificationCodeMail extends Mailable
{
    public function __construct(
        public User $user,
        public string $code,
        public int $ttlMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth.mail.subject', ['code' => $this->code]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verification-code');
    }
}
