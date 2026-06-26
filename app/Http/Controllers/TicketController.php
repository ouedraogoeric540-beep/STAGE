<?php

namespace App\Http\Controllers;

use App\Models\CategorieTicket;
use App\Models\Evenement;
use App\Models\Participant;
use App\Models\Paiement;
use App\Models\Ticket;
use App\Models\LogSysteme;
use App\Services\PDFService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function __construct(
        protected PDFService   $pdfService,
        protected EmailService $emailService
    ) {}

    // Réserver un ticket (public)
 public function reserver(Request $request)
{
    $request->validate([
        'nom'          => 'required|string|max:191',
        'prenom'       => 'nullable|string|max:191',
        'sexe'         => 'nullable|in:M,F',
        'email'        => 'required|email|max:191',
        'telephone'    => 'nullable|string|max:191',
        'evenement_id' => 'required|exists:evenements,id',
        'categorie_id' => 'required|exists:categories_tickets,id',
    ]);

    $evenement = Evenement::findOrFail($request->evenement_id);

    if ($evenement->statut !== 'actif') {
        return response()->json(['message' => 'Cet événement n\'est plus disponible.'], 422);
    }

    $categorie = CategorieTicket::where('evenement_id', $evenement->id)
        ->findOrFail($request->categorie_id);


    if (!$categorie->estDisponible()) {
        return response()->json(['message' => 'Plus de places disponibles dans cette catégorie.'], 422);
    }

    // Résoudre le user connecté (même sur une route publique)
    $userId = auth('sanctum')->id();

    // Créer ou retrouver le participant
    $participant = Participant::firstOrCreate(
        ['email' => $request->email],
        [
            'nom'       => $request->nom,
            'prenom'    => $request->prenom,
            'sexe'      => $request->sexe,
            'telephone' => $request->telephone,
            'a_compte'  => (bool) $userId,
            'user_id'   => $userId,
        ]
    );

    // Toujours mettre à jour le user_id si connecté et pas encore lié
    if ($userId && !$participant->user_id) {
        $participant->update([
            'user_id'  => $userId,
            'a_compte' => true,
        ]);
    }

    // Créer le ticket
    $ticket = Ticket::create([
        'participant_id' => $participant->id,
        'evenement_id'   => $evenement->id,
        'categorie_id'   => $categorie->id,
        'statut'         => 'valide',
        'prix_paye'      => $categorie->prix 

    ]);

    // Créer le paiement — avec un petit délai pour s'assurer que le ticket est bien sauvegardé
    $paiement = Paiement::create([
        'ticket_id' => $ticket->id,
        'montant'   => $categorie->prix ,
        'methode'   => 'simulation',
        'statut'    => 'en_attente',
    ]);

    // Recharger le ticket avec ses relations
    $ticket->load(['evenement', 'categorie', 'participant', 'paiement']);

    return response()->json([
        'message'  => 'Réservation créée. Procédez au paiement.',
        'ticket'   => $ticket,
        'paiement' => $paiement,
    ], 201);
}
    // Confirmer le paiement (public)
   public function confirmerPaiement(Request $request, $id)
{
    // Chercher par ID ou par code_unique
    $ticket = Ticket::with(['participant', 'evenement', 'categorie'])
        ->findOrFail($id);

    // Chercher le paiement en attente
    $paiement = Paiement::where('ticket_id', $ticket->id)
        ->where('statut', 'en_attente')
        ->first();

    // Si pas de paiement en attente, on en crée un
    if (!$paiement) {
        $paiement = Paiement::where('ticket_id', $ticket->id)->first();

        // Si aucun paiement du tout, on en crée un
        if (!$paiement) {
            $paiement = Paiement::create([
                'ticket_id' => $ticket->id,
                'montant'   => $ticket->prix_paye,
                'methode'   => 'simulation',
                'statut'    => 'en_attente',
            ]);
        }

        // Si déjà validé
        if ($paiement->statut === 'valide') {
            return response()->json([
                'message' => 'Ce paiement a déjà été validé.',
                'ticket'  => $ticket->load('paiement'),
            ], 422);
        }
    }

    // Valider le paiement
    $paiement->update([
        'statut'        => 'valide',
        'date_paiement' => now(),
    ]);

    // Incrémenter quantité vendue
    $ticket->categorie->increment('quantite_vendue', $ticket->quantite ?? 1);

    // Dispatch the job to generate PDF and send email asynchronously
    \App\Jobs\ProcessTicketPostPayment::dispatchAfterResponse($ticket);

    LogSysteme::create([
        'action'  => 'Paiement confirmé',
        'details' => 'Ticket #' . $ticket->code_unique . ' — ' . $ticket->evenement->titre,
    ]);

    return response()->json([
        'message'  => 'Paiement confirmé. Ticket généré et envoyé par email.',
        'ticket'   => $ticket->fresh()->load(['participant', 'evenement', 'categorie', 'paiement']),
        'paiement' => $paiement->fresh(),
    ]);
}
    // Mes tickets (participant connecté)
    public function mesTickets(Request $request)
    {
        $user = $request->user();

        // Filet de sécurité : lier le participant à l'utilisateur actuel
        Participant::where('email', $user->email)
            ->update(['user_id' => $user->id, 'a_compte' => true]);

        // On charge les relations nécessaires pour éviter les erreurs d'affichage
        $tickets = Ticket::with(['evenement', 'categorie', 'paiement', 'participant'])
            ->whereHas('participant', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    // Télécharger PDF
    public function telechargerPdf(Request $request, $id)
    {
        try {
            $ticket = Ticket::with(['participant', 'evenement', 'categorie', 'paiement'])
                ->findOrFail($id);

            // Générer si absent
            if (!$ticket->pdf_path || !file_exists(storage_path('app/public/' . $ticket->pdf_path))) {
                $cheminPdf = $this->pdfService->genererTicketPDF($ticket);
                $ticket->update(['pdf_path' => $cheminPdf]);
                $ticket->refresh();
            }

            $cheminAbsolu = storage_path('app/public/' . $ticket->pdf_path);

            if (!file_exists($cheminAbsolu)) {
                return response()->json(['message' => 'Fichier introuvable.'], 404);
            }

            // Lire le contenu et retourner en base64 pour éviter les problèmes CORS
            $contenu = file_get_contents($cheminAbsolu);
            $base64  = base64_encode($contenu);

            return response()->json([
                'base64'   => $base64,
                'filename' => 'ticket_' . $ticket->code_unique . '.pdf',
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}