<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;

    // Receive user's name
    public function __construct($name)
    {
        $this->name = $name;
    }

    // Email subject
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Restaurant Management!',
        );
    }

    // Email view and data
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: [
                'name' => $this->name,
            ],
        );
    }
}
