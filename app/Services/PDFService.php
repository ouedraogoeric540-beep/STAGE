<?php

namespace App\Services;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PDFService
{
    protected QRCodeService $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function genererTicketPDF(Ticket $ticket): string
    {
        // Charger toutes les relations
        $ticket->load(['participant', 'evenement', 'categorie', 'paiement']);

        // Générer QR Code
        $qrCodeBase64 = $this->qrCodeService->genererDataUri($ticket->qr_code);

        // Créer le dossier si inexistant
        $dossier = storage_path('app/public/tickets');
        if (!file_exists($dossier)) {
            mkdir($dossier, 0755, true);
        }

        // Générer le PDF
        $pdf = Pdf::loadView('tickets.pdf', [
            'ticket'       => $ticket,
            'qrCodeBase64' => $qrCodeBase64,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'defaultFont'          => 'DejaVu Sans',
            'dpi'                  => 150,
        ]);

        // Nom du fichier
        $nomFichier = 'ticket_' . $ticket->code_unique . '.pdf';
        $chemin     = 'tickets/' . $nomFichier;
        $cheminAbsolu = storage_path('app/public/' . $chemin);

        // Sauvegarder
        file_put_contents($cheminAbsolu, $pdf->output());

        Log::info('PDF généré : ' . $cheminAbsolu);

        return $chemin;
    }
}