<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\PDFService;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTicketPostPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $ticket;

    /**
     * Create a new job instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Execute the job.
     */
    public function handle(PDFService $pdfService, EmailService $emailService): void
    {
        // Générer le PDF
        try {
            $cheminPdf = $pdfService->genererTicketPDF($this->ticket);
            $this->ticket->update(['pdf_path' => $cheminPdf]);
        } catch (\Exception $e) {
            Log::error('Erreur PDF Job : ' . $e->getMessage());
        }

        // Envoyer l'email
        try {
            $this->ticket->refresh()->load(['participant', 'evenement', 'categorie', 'paiement']);
            $emailService->envoyerTicket($this->ticket);
        } catch (\Exception $e) {
            Log::error('Erreur Email Job : ' . $e->getMessage());
        }
    }
}
