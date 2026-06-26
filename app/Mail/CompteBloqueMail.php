<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompteBloqueMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $debloquageAt;

    public function __construct($user, $debloquageAt)
    {
        $this->user = $user;
        $this->debloquageAt = $debloquageAt;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Alerte Sécurité - Votre compte SecurePass a été bloqué',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.compte_bloque',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
