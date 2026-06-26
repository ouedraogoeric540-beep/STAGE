<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\CategorieTicket;
use App\Models\LogSysteme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvenementController extends Controller
{
    // Liste publique — événements actifs uniquement
    public function index()
    {
        $evenements = Evenement::with(['organisateur:id,name', 'categories'])
            ->where('statut', 'actif')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($evenements);
    }

    // Détail public d'un événement
    public function show($id)
    {
        $evenement = Evenement::with(['organisateur:id,name', 'categories'])
            ->findOrFail($id);

        return response()->json($evenement);
    }

    // Mes événements (organisateur)
    public function mesEvenements(Request $request)
    {
        $evenements = Evenement::with(['categories'])
            ->where('organisateur_id', $request->user()->id)
            ->withCount('tickets')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($evenements);
    }

    // Statistiques par sexe (organisateur)
    public function statsSexe(Request $request)
    {
        $organisateurId = $request->user()->id;

        $evenements = Evenement::where('organisateur_id', $organisateurId)
            ->with(['tickets.participant'])
            ->get();

        $stats = $evenements->map(function ($event) {
            $femmes = $event->tickets->filter(fn($t) => $t->participant && $t->participant->sexe === 'F')->count();
            $hommes = $event->tickets->filter(fn($t) => $t->participant && $t->participant->sexe === 'M')->count();
            $nonRenseigne = $event->tickets->filter(fn($t) => !$t->participant || !$t->participant->sexe)->count();

            return [
                'id' => $event->id,
                'titre' => $event->titre,
                'femmes' => $femmes,
                'hommes' => $hommes,
                'non_renseigne' => $nonRenseigne,
                'total' => $femmes + $hommes + $nonRenseigne
            ];
        });

        return response()->json($stats);
    }

    // Créer un événement
    public function store(Request $request)
    {
        $request->validate([
            'titre'        => 'required|string|max:191',
            'description'  => 'nullable|string',
            'date'         => 'required|date|after:now',
            'lieu'         => 'required|string|max:191',
            'capacite_max' => 'required|integer|min:1',
            'image'        => 'nullable|image|max:2048',
            'categories'   => 'required|string', // JSON string
        ]);

        // Upload image
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('evenements', 'public');
        }

        // Décoder les catégories
        $categories = json_decode($request->categories, true);
        if (!$categories || !is_array($categories)) {
            return response()->json(['message' => 'Catégories invalides.'], 422);
        }

        $evenement = Evenement::create([
            'organisateur_id' => $request->user()->id,
            'titre'           => $request->titre,
            'description'     => $request->description,
            'date'            => $request->date,
            'lieu'            => $request->lieu,
            'capacite_max'    => $request->capacite_max,
            'image'           => $imagePath,
            'statut'          => 'actif',
        ]);

        // Créer les catégories
        foreach ($categories as $cat) {
            CategorieTicket::create([
                'evenement_id'   => $evenement->id,
                'nom'            => $cat['nom'],
                'prix'           => $cat['prix'],
                'quantite_total' => $cat['quantite_total'],
            ]);
        }

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Création événement',
            'details' => 'Événement créé : ' . $evenement->titre,
        ]);

        return response()->json(
            $evenement->load('categories'),
            201
        );
    }

    // Modifier un événement
    public function update(Request $request, $id)
    {
        $evenement = Evenement::where('organisateur_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'titre'        => 'sometimes|string|max:191',
            'description'  => 'nullable|string',
            'date'         => 'sometimes|date',
            'lieu'         => 'sometimes|string|max:191',
            'capacite_max' => 'sometimes|integer|min:1',
            'statut'       => 'sometimes|in:actif,termine,annule',
            'image'        => 'nullable|image|max:2048',
            'categories'   => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            if ($evenement->image) {
                Storage::disk('public')->delete($evenement->image);
            }
            $evenement->image = $request->file('image')->store('evenements', 'public');
        }

        $evenement->update($request->except(['image', 'categories']));

        if ($request->has('categories')) {
            $categories = json_decode($request->categories, true);
            if ($categories && is_array($categories)) {
                $evenement->categories()->delete();
                foreach ($categories as $cat) {
                    CategorieTicket::create([
                        'evenement_id'   => $evenement->id,
                        'nom'            => $cat['nom'],
                        'prix'           => $cat['prix'],
                        'quantite_total' => $cat['quantite_total'],
                    ]);
                }
            }
        }

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Modification événement',
            'details' => 'Événement modifié : ' . $evenement->titre,
        ]);

        return response()->json($evenement->load('categories'));
    }

    // Supprimer un événement
    public function destroy(Request $request, $id)
    {
        $evenement = Evenement::where('organisateur_id', $request->user()->id)
            ->findOrFail($id);

        if ($evenement->image) {
            Storage::disk('public')->delete($evenement->image);
        }

        $titre = $evenement->titre;
        $evenement->delete();

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Suppression événement',
            'details' => 'Événement supprimé : ' . $titre,
        ]);

        return response()->json(['message' => 'Événement supprimé.']);
    }

    // Tous les événements (admin)
    public function adminIndex()
    {
        $evenements = Evenement::with(['organisateur:id,name'])
            ->withCount('tickets')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($evenements);
    }

    // Exporter les participants d'un événement (CSV)
    public function exportParticipants(Request $request, $id)
    {
        $evenement = Evenement::where('organisateur_id', $request->user()->id)->findOrFail($id);
        $tickets = \App\Models\Ticket::with(['participant', 'categorie'])
            ->where('evenement_id', $evenement->id)
            ->get();

        $filename = "participants_evenement_{$evenement->id}.csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Nom', 'Prénom', 'Sexe', 'Email', 'Téléphone', 'Type Ticket', 'Prix Payé', 'Statut'];

        $callback = function() use($tickets, $columns) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, $columns, ';');

            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->participant->nom ?? '',
                    $ticket->participant->prenom ?? '',
                    $ticket->participant->sexe ?? '',
                    $ticket->participant->email ?? '',
                    $ticket->participant->telephone ?? '',
                    $ticket->categorie->nom ?? '',
                    $ticket->prix_paye ?? 0,
                    $ticket->statut ?? ''
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Historique des revenus (organisateur)
    public function historiqueRevenus(Request $request)
    {
        $organisateurId = $request->user()->id;

        $tickets = \App\Models\Ticket::whereHas('evenement', function($q) use ($organisateurId) {
            $q->where('organisateur_id', $organisateurId);
        })
        ->where('statut', 'valide')
        ->orderBy('created_at', 'asc')
        ->get();

        $revenusParJour = $tickets->groupBy(function($t) {
            return \Carbon\Carbon::parse($t->created_at)->format('Y-m-d');
        })->map(function ($rows) {
            return $rows->sum('prix_paye');
        });

        $result = [];
        foreach ($revenusParJour as $date => $revenu) {
            $result[] = [
                'date' => $date,
                'revenu' => $revenu
            ];
        }

        return response()->json($result);
    }
}