<?php

namespace App\Mail;

use App\Models\Entrepreneur;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEntrepreneurMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Entrepreneur $entrepreneur) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.welcome.subject', ['name' => $this->entrepreneur->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
        );
    }
}
