<?php

namespace App\Http\Controllers;

use App\Models\ScanQr;
use App\Models\Ticket;
use App\Models\LogSysteme;
use Illuminate\Http\Request;

class ScanQRController extends Controller
{
    // Scanner un QR Code
    public function scanner(Request $request)
    {
        $request->validate([
            'qr_code'      => 'required|string',
            'evenement_id' => 'required|exists:evenements,id',
        ]);

        $agent       = $request->user();
        $evenementId = $request->evenement_id;
        $qrCode      = $request->qr_code;

        // Vérifier affectation agent
        $affecte = $agent->agentEvenements()
            ->where('evenement_id', $evenementId)
            ->where('actif', true)
            ->exists();

        if (!$affecte) {
            return response()->json([
                'message'  => 'Vous n\'êtes pas affecté à cet événement.',
                'resultat' => 'invalide',
            ], 403);
        }

        // Chercher par qr_code (scan caméra) OU par code_unique (saisie manuelle)
        $ticket = Ticket::where('qr_code', $qrCode)
            ->orWhere('code_unique', strtoupper($qrCode))
            ->first();

        $resultat = 'invalide';
        $message  = 'QR Code invalide ou introuvable.';

        if ($ticket) {
            if ($ticket->evenement_id != $evenementId) {
                $resultat = 'mauvais_evenement';
                $message  = 'Ce ticket n\'appartient pas à cet événement.';
            } elseif ($ticket->statut === 'utilise') {
                $resultat = 'deja_utilise';
                $message  = 'Ce ticket a déjà été utilisé.';
            } elseif ($ticket->statut === 'expire') {
                $resultat = 'invalide';
                $message  = 'Ce ticket est expiré.';
            } elseif ($ticket->statut === 'valide') {
                $resultat = 'valide';
                $message  = 'Accès autorisé. Bienvenue !';
                $ticket->update(['statut' => 'utilise']);
            }
        }

        // Enregistrer le scan
        ScanQr::create([
            'qr_code'      => $qrCode,
            'ticket_id'    => $ticket?->id,
            'agent_id'     => $agent->id,
            'evenement_id' => $evenementId,
            'resultat'     => $resultat,
            'date_scan'    => now(),
        ]);

        LogSysteme::create([
            'user_id' => $agent->id,
            'action'  => 'Scan QR Code',
            'details' => 'Résultat : ' . $resultat . ' — Code : ' . $qrCode,
        ]);

        return response()->json([
            'resultat' => $resultat,
            'message'  => $message,
            'ticket'   => $ticket?->load(['participant', 'evenement', 'categorie']),
        ]);
    }

    // Historique des scans de l'agent
    public function historique(Request $request)
    {
        $query = ScanQr::with(['evenement:id,titre', 'ticket.participant'])
            ->where('agent_id', $request->user()->id);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('qr_code', 'like', "%{$search}%")
                  ->orWhereHas('ticket.participant', function($q2) use ($search) {
                      $q2->where('nom', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('statut') && !empty($request->statut)) {
            $query->where('resultat', $request->statut);
        }

        $scans = $query->orderBy('date_scan', 'desc')->paginate(20);

        return response()->json($scans);
    }

    // Événements assignés à l'agent
    public function evenementsAgent(Request $request)
    {
        $agentId = $request->user()->id;

        $evenements = $request->user()
            ->agentEvenements()
            ->wherePivot('actif', true)
            ->where('evenements.statut', 'actif')
            ->withCount([
                'scans' => function ($query) use ($agentId) {
                    $query->where('agent_id', $agentId);
                },
                'scans as scans_valides_count' => function ($query) use ($agentId) {
                    $query->where('agent_id', $agentId)->where('resultat', 'valide');
                },
                'scans as scans_invalides_count' => function ($query) use ($agentId) {
                    $query->where('agent_id', $agentId)->where('resultat', '!=', 'valide');
                }
            ])
            ->with('categories')
            ->get();
            
        // Ajouter la capacité max calculée (somme des catégories ou fallback)
        $evenements->each(function ($ev) {
            $ev->capacite_max_calculee = $ev->categories->sum('quantite_total') ?: $ev->capacite_max;
        });

        return response()->json($evenements);
    }

    // Signaler un problème (Alerte par l'agent)
    public function alerte(Request $request)
    {
        $request->validate([
            'message'      => 'required|string',
            'evenement_id' => 'nullable|exists:evenements,id',
        ]);

        $agent = $request->user();

        LogSysteme::create([
            'user_id' => $agent->id,
            'action'  => 'Alerte Urgence',
            'details' => 'Signalement par l\'agent : ' . $request->message . ($request->evenement_id ? ' (Événement ID: ' . $request->evenement_id . ')' : ''),
        ]);

        // Créer une notification pour l'organisateur
        if ($request->evenement_id) {
            $evenement = \App\Models\Evenement::find($request->evenement_id);
            if ($evenement) {
                \App\Models\NotificationEventsecure::create([
                    'user_id'    => $evenement->organisateur_id,
                    'type'       => 'alerte',
                    'contenu'    => 'Alerte agent ' . $agent->name . ' : ' . $request->message . ' (Événement ID: ' . $evenement->id . ')',
                    'statut'     => 'non_lu',
                    'date_envoi' => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Alerte signalée avec succès.']);
    }

    // Logs des scans pour l'organisateur (Suivi en temps réel)
    public function organisateurLogs(Request $request)
    {
        $organisateurId = $request->user()->id;

        $evenementIds = \App\Models\Evenement::where('organisateur_id', $organisateurId)->pluck('id');

        $scans = ScanQr::with(['evenement:id,titre,capacite_max', 'agent:id,name', 'ticket.participant'])
            ->whereIn('evenement_id', $evenementIds)
            ->orderBy('date_scan', 'desc')
            ->paginate(30);

        $totalCapacite = \App\Models\Evenement::whereIn('id', $evenementIds)->where('statut', 'actif')->sum('capacite_max');
        $totalScannes = ScanQr::whereIn('evenement_id', $evenementIds)->where('resultat', 'valide')->count();

        // Récupérer les événements actifs pour les stats détaillées
        $evenementsActifs = \App\Models\Evenement::whereIn('id', $evenementIds)->where('statut', 'actif')->get(['id', 'titre', 'capacite_max']);
        
        // Requête groupée pour les scans valides par événement
        $scansParEvenement = ScanQr::select('evenement_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereIn('evenement_id', $evenementsActifs->pluck('id'))
            ->where('resultat', 'valide')
            ->groupBy('evenement_id')
            ->pluck('total', 'evenement_id');

        $statsParEvenement = [];
        foreach ($evenementsActifs as $ev) {
            $statsParEvenement[] = [
                'id' => $ev->id,
                'titre' => $ev->titre,
                'capacite_max' => $ev->capacite_max,
                'total_scannes' => $scansParEvenement->get($ev->id, 0)
            ];
        }

        return response()->json([
            'scans' => $scans,
            'stats' => [
                'total_scannes' => $totalScannes,
                'capacite_totale' => $totalCapacite,
            ],
            'stats_par_evenement' => $statsParEvenement
        ]);
    }

    // --- OFFLINE SCAN SUPPORT ---
    
    // Récupère les hashs de tous les tickets valides d'un événement pour stockage local (offline)
    public function getTicketsHashes(Request $request, $evenementId)
    {
        $agent = $request->user();

        // Vérifier affectation
        $affecte = $agent->agentEvenements()
            ->where('evenement_id', $evenementId)
            ->where('actif', true)
            ->exists();

        if (!$affecte) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // On renvoie juste le strict minimum pour la base locale de l'agent
        $tickets = Ticket::where('evenement_id', $evenementId)
            ->get(['id', 'qr_code', 'code_unique', 'statut']);

        return response()->json($tickets);
    }

    // Synchronise une liste de scans effectués en mode hors-ligne
    public function syncOfflineScans(Request $request)
    {
        $request->validate([
            'scans' => 'required|array',
            'scans.*.qr_code' => 'required|string',
            'scans.*.evenement_id' => 'required|exists:evenements,id',
            'scans.*.date_scan' => 'required|date',
            'scans.*.resultat' => 'required|string',
        ]);

        $agent = $request->user();
        $scans = $request->scans;
        $syncedCount = 0;
        $errors = [];

        foreach ($scans as $scanData) {
            $evenementId = $scanData['evenement_id'];
            $qrCode = $scanData['qr_code'];
            $dateScan = $scanData['date_scan'];
            $resultatLocal = $scanData['resultat']; // valide, deja_utilise, mauvais_evenement, invalide

            // Vérifier affectation agent pour cet evt (important s'il a scanné plusieurs events)
            $affecte = $agent->agentEvenements()->where('evenement_id', $evenementId)->where('actif', true)->exists();
            if (!$affecte) {
                $errors[] = ['qr_code' => $qrCode, 'reason' => 'non_affecte'];
                continue;
            }

            // Mettre à jour le ticket réel s'il était valide localement
            $ticket = Ticket::where('qr_code', $qrCode)->orWhere('code_unique', strtoupper($qrCode))->first();
            
            $resultatFinal = $resultatLocal;

            if ($ticket) {
                // S'il était marqué comme valide hors ligne, on le met à jour côté serveur 
                // MAIS s'il a déjà été utilisé entre-temps par un autre agent, on a un conflit.
                if ($resultatLocal === 'valide') {
                    if ($ticket->statut === 'valide') {
                        $ticket->update(['statut' => 'utilise']);
                    } else {
                        // Conflit : le ticket est déjà utilisé/expiré sur le serveur
                        $resultatFinal = 'conflit_deja_utilise';
                    }
                }
            } else {
                $resultatFinal = 'invalide'; // Le ticket n'existe pas en vrai
            }

            // Enregistrer le scan
            ScanQr::create([
                'qr_code'      => $qrCode,
                'ticket_id'    => $ticket?->id,
                'agent_id'     => $agent->id,
                'evenement_id' => $evenementId,
                'resultat'     => $resultatFinal,
                'date_scan'    => $dateScan, // On utilise l'heure locale de l'appareil
            ]);

            $syncedCount++;
        }

        LogSysteme::create([
            'user_id' => $agent->id,
            'action'  => 'Synchronisation Offline',
            'details' => $syncedCount . ' scans synchronisés.',
        ]);

        return response()->json([
            'message' => 'Synchronisation terminée',
            'synced_count' => $syncedCount,
            'errors' => $errors
        ]);
    }
}