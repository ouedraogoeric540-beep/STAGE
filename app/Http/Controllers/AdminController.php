<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\LogSysteme;
use App\Models\Paiement;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AgentAffectationMail;

class AdminController extends Controller
{
    // Statistiques globales
    public function statistiques()
    {
        return response()->json([
            'utilisateurs'      => User::count(),
            'organisateurs'     => User::where('role', 'organisateur')->count(),
            'agents'            => User::where('role', 'agent')->count(),
            'evenements_actifs' => Evenement::where('statut', 'actif')->count(),
            'evenements_termines'=> Evenement::where('statut', 'termine')->count(),
            'tickets_valides'   => Ticket::where('statut', 'valide')->count(),
            'tickets_utilises'  => Ticket::where('statut', 'utilise')->count(),
            'tickets_expires'   => Ticket::where('statut', 'expire')->count(),
            'revenus_total'     => Paiement::where('statut', 'valide')->sum('montant'),
            'participants_femmes'=> \App\Models\Participant::where('sexe', 'F')->count(),
            'participants_hommes'=> \App\Models\Participant::where('sexe', 'M')->count(),
        ]);
    }

    public function users(Request $request)
    {
        $query = User::with(['agentEvenements' => function ($q) {
            $q->select('evenements.id', 'titre'); // Load event details
        }]);

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    // Créer un compte
    public function createUser(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:191',
            'prenom'    => 'nullable|string|max:191',
            'sexe'      => 'nullable|in:M,F',
            'email'     => 'required|email|max:191|unique:users',
            'password'  => 'required|string|min:8',
            'role'      => 'required|in:admin,organisateur,agent,participant',
            'telephone' => 'nullable|string|max:191',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'prenom'    => $request->prenom,
            'sexe'      => $request->sexe,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'statut'    => true,
            'telephone' => $request->telephone,
        ]);

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Création compte',
            'details' => 'Compte ' . $request->role . ' créé : ' . $user->email,
        ]);

        return response()->json($user, 201);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'      => 'required|string|max:191',
            'prenom'    => 'nullable|string|max:191',
            'sexe'      => 'nullable|in:M,F',
            'email'     => 'required|email|max:191|unique:users,email,' . $id,
            'password'  => 'nullable|string|min:8',
            'role'      => 'required|in:admin,organisateur,agent,participant',
            'telephone' => 'nullable|string|max:191',
        ]);

        $data = $request->only(['name', 'prenom', 'sexe', 'email', 'role', 'telephone']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Modification compte',
            'details' => 'Compte ' . $user->role . ' modifié : ' . $user->email,
        ]);

        return response()->json($user);
    }

    // Affecter un agent à plusieurs événements (admin)
    public function affecterAgent(Request $request)
    {
        $request->validate([
            'agent_id'      => 'required|exists:users,id',
            'evenement_ids' => 'required|array',
            'evenement_ids.*' => 'exists:evenements,id',
        ]);

        $agent = User::where('role', 'agent')->findOrFail($request->agent_id);
        $count = 0;

        foreach ($request->evenement_ids as $evenement_id) {
            $evenement = Evenement::find($evenement_id);
            if (!$evenement) continue;

            $exists = DB::table('agent_evenement')
                ->where('agent_id', $agent->id)
                ->where('evenement_id', $evenement->id)
                ->exists();

            if ($exists) {
                DB::table('agent_evenement')
                    ->where('agent_id', $agent->id)
                    ->where('evenement_id', $evenement->id)
                    ->update(['actif' => true]);
            } else {
                DB::table('agent_evenement')->insert([
                    'agent_id'         => $agent->id,
                    'evenement_id'     => $evenement->id,
                    'actif'            => true,
                    'date_affectation' => now(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
                $prefs = $agent->notif_prefs ?? null;
                $wantsEmail = true;
                if (is_array($prefs) && array_key_exists('agentAssigned', $prefs)) {
                    $wantsEmail = $prefs['agentAssigned'];
                }

                if ($wantsEmail) {
                    Mail::to($agent->email)->send(new AgentAffectationMail($agent, $evenement));
                }
            }
            $count++;
        }

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Affectation agent multiple (Admin)',
            'details' => "Agent {$agent->email} affecté à {$count} événement(s)",
        ]);

        return response()->json(['message' => 'Agent affecté avec succès.']);
    }

    // Toggle statut utilisateur
    public function toggleUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update(['statut' => !$user->statut]);

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => $user->statut ? 'Activation compte' : 'Désactivation compte',
            'details' => 'Utilisateur : ' . $user->email,
        ]);

        return response()->json([
            'message' => 'Statut mis à jour.',
            'statut'  => $user->statut,
        ]);
    }

    // Logs système
    public function logs()
    {
        $logs = LogSysteme::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(6);

        return response()->json($logs);
    }

    // Tous les événements
    public function evenements()
    {
        $evenements = Evenement::with(['organisateur:id,name', 'categories'])
            ->withCount('tickets')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($evenements);
    }

    // Créer un événement (admin)
    public function createEvenement(Request $request)
    {
        $request->validate([
            'titre'        => 'required|string|max:191',
            'description'  => 'nullable|string',
            'date'         => 'required|date',
            'lieu'         => 'required|string|max:191',
            'capacite_max' => 'required|integer|min:1',
            'image'        => 'nullable|image|max:2048',
            'categories'   => 'required|string',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('evenements', 'public');
        }

        $categories = json_decode($request->categories, true);
        if (!$categories || !is_array($categories)) {
            return response()->json(['message' => 'Catégories invalides.'], 422);
        }

        $evenement = \App\Models\Evenement::create([
            'organisateur_id' => $request->user()->id,
            'titre'           => $request->titre,
            'description'     => $request->description,
            'date'            => $request->date,
            'lieu'            => $request->lieu,
            'capacite_max'    => $request->capacite_max,
            'image'           => $imagePath,
            'statut'          => 'actif',
        ]);

        foreach ($categories as $cat) {
            \App\Models\CategorieTicket::create([
                'evenement_id'   => $evenement->id,
                'nom'            => $cat['nom'],
                'prix'           => $cat['prix'],
                'quantite_total' => $cat['quantite_total'],
            ]);
        }

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Création événement (Admin)',
            'details' => 'Événement créé : ' . $evenement->titre,
        ]);

        return response()->json($evenement->load('categories'), 201);
    }

    // Modifier un événement (admin)
    public function updateEvenement(Request $request, $id)
    {
        $evenement = \App\Models\Evenement::findOrFail($id);

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
                \Illuminate\Support\Facades\Storage::disk('public')->delete($evenement->image);
            }
            $evenement->image = $request->file('image')->store('evenements', 'public');
        }

        $evenement->update($request->except(['image', 'categories']));

        if ($request->has('categories')) {
            $categories = json_decode($request->categories, true);
            if ($categories && is_array($categories)) {
                $evenement->categories()->delete();
                foreach ($categories as $cat) {
                    \App\Models\CategorieTicket::create([
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
            'action'  => 'Modification événement (Admin)',
            'details' => 'Événement modifié : ' . $evenement->titre,
        ]);

        return response()->json($evenement->load('categories'));
    }

    // Supprimer un événement (admin)
    public function deleteEvenement(Request $request, $id)
    {
        $evenement = \App\Models\Evenement::findOrFail($id);

        if ($evenement->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($evenement->image);
        }

        $titre = $evenement->titre;
        $evenement->delete();

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Suppression événement (Admin)',
            'details' => 'Événement supprimé : ' . $titre,
        ]);

        return response()->json(['message' => 'Événement supprimé.']);
    }

    // Sauvegarde globale de la base de données (JSON)
    public function backup(Request $request)
    {
        // Récupérer toutes les tables
        $tables = DB::select('SHOW TABLES');
        $dbName = env('DB_DATABASE', 'eventsecure');
        $property = 'Tables_in_' . $dbName;
        
        $backupData = [];

        foreach ($tables as $tableInfo) {
            // Selon le nom de la base, la propriété peut différer
            $tableName = null;
            foreach ($tableInfo as $key => $value) {
                if (str_starts_with($key, 'Tables_in_')) {
                    $tableName = $value;
                    break;
                }
            }

            if (!$tableName) {
                // Fallback si la structure est différente
                $tableName = array_values((array)$tableInfo)[0];
            }

            // Ignorer les tables inutiles pour un backup (ex: migrations)
            if ($tableName === 'migrations' || $tableName === 'personal_access_tokens') {
                continue;
            }

            $backupData[$tableName] = DB::table($tableName)->get();
        }

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Sauvegarde Globale',
            'details' => 'Export JSON de toutes les tables de la base.',
            'ip_address' => $request->ip()
        ]);

        $fileName = 'backup_securepass_' . date('Y-m-d_H-i-s') . '.json';

        return response()->streamDownload(function () use ($backupData) {
            echo json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }
}