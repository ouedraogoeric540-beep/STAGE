<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Services\QRCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎟️ Votre ticket — ' . $this->ticket->evenement->titre,
        );
    }

    public function content(): Content
    {
        $qrCodeService = new QRCodeService();
        $qrCodeBase64  = $qrCodeService->genererDataUri($this->ticket->qr_code);

        return new Content(
            view: 'emails.ticket',
            with: [
                'ticket'       => $this->ticket,
                'qrCodeBase64' => $qrCodeBase64,
            ],
        );
    }

    public function attachments(): array
    {
        // PDF optionnel — pas d'erreur si absent
        if (
            $this->ticket->pdf_path &&
            Storage::disk('public')->exists($this->ticket->pdf_path)
        ) {
            return [
                Attachment::fromPath(
                    Storage::disk('public')->path($this->ticket->pdf_path)
                )->as('ticket_' . $this->ticket->code_unique . '.pdf')
                 ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}