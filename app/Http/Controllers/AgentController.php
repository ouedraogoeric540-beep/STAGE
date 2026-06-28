<?php

namespace App\Http\Controllers;

use App\Models\AgentEvenement;
use App\Models\LogSysteme;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AgentAffectationMail;

class AgentController extends Controller
{
    public function update(Request $request, $id)
    {
        $agent = User::where('role', 'agent')
            ->where('organisateur_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'name'      => 'required|string|max:191',
            'prenom'    => 'nullable|string|max:191',
            'sexe'      => 'nullable|in:M,F',
            'email'     => 'required|email|max:191|unique:users,email,' . $id,
            'password'  => 'nullable|string|min:8',
            'telephone' => 'nullable|string|max:191',
        ]);

        $data = $request->only(['name', 'prenom', 'sexe', 'email', 'telephone']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $agent->update($data);

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Modification agent',
            'details' => 'Agent modifié : ' . $agent->email,
        ]);

        return response()->json($agent);
    }

    // Liste des agents de l'organisateur
public function index(Request $request)
{
    $evenementIds = \App\Models\Evenement::where('organisateur_id', $request->user()->id)
        ->pluck('id');

    $agents = User::where('role', 'agent')
        ->whereIn('id', function ($q) use ($evenementIds) {
            $q->select('agent_id')
              ->from('agent_evenement')
              ->whereIn('evenement_id', $evenementIds);
        })
        ->orWhere(function ($q) {
            $q->where('role', 'agent');
        })
        ->with(['agentEvenements' => function ($q) use ($evenementIds) {
            $q->whereIn('evenements.id', $evenementIds);
        }])
        ->withCount(['agentEvenements as affectations_count' => function ($q) use ($evenementIds) {
            $q->whereIn('evenement_id', $evenementIds);
        }])
        ->get();

    return response()->json($agents);
}
    // Créer un agent
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:191',
            'prenom'    => 'nullable|string|max:191',
            'sexe'      => 'nullable|in:M,F',
            'email'     => 'required|email|max:191|unique:users',
            'password'  => 'required|string|min:8',
            'telephone' => 'nullable|string|max:191',
        ]);

        $agent = User::create([
            'name'      => $request->name,
            'prenom'    => $request->prenom,
            'sexe'      => $request->sexe,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'agent',
            'statut'    => true,
            'telephone' => $request->telephone,
        ]);

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Création agent',
            'details' => 'Agent créé : ' . $agent->email,
        ]);

        return response()->json($agent, 201);
    }

    // Affecter un agent à un événement
    public function affecter(Request $request)
    {
        $request->validate([
            'agent_id'      => 'required|exists:users,id',
            'evenement_ids' => 'required|array',
            'evenement_ids.*' => 'exists:evenements,id',
        ]);

        $agent = User::where('role', 'agent')->findOrFail($request->agent_id);
        
        // Liste des événements appartenant à l'organisateur
        $organisateurEvenements = \App\Models\Evenement::where('organisateur_id', $request->user()->id)
            ->pluck('id')->toArray();
        
        // Les événements soumis qui appartiennent réellement à l'organisateur
        $evenementsValides = array_intersect($request->evenement_ids, $organisateurEvenements);

        // Désactiver (ou supprimer) les affectations existantes pour CET organisateur
        // qui ne sont pas dans les événements cochés
        DB::table('agent_evenement')
            ->where('agent_id', $agent->id)
            ->whereIn('evenement_id', $organisateurEvenements)
            ->whereNotIn('evenement_id', $evenementsValides)
            ->delete();

        $count = 0;

        foreach ($evenementsValides as $evenement_id) {
            $evenement = \App\Models\Evenement::find($evenement_id);
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
                
                try {
                    $prefs = $agent->notif_prefs ?? null;
                    $wantsEmail = true;
                    if (is_array($prefs) && array_key_exists('agentAssigned', $prefs)) {
                        $wantsEmail = $prefs['agentAssigned'];
                    }

                    if ($wantsEmail) {
                        Mail::to($agent->email)->send(new AgentAffectationMail($agent, $evenement));
                    }
                } catch (\Exception $e) {
                    // Ignorer silencieusement si l'email échoue pour ne pas bloquer l'affectation
                }
            }
            $count++;
        }

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Affectation agent multiple',
            'details' => "Agent {$agent->email} affecté à {$count} événement(s)",
        ]);

        return response()->json(['message' => 'Agent affecté avec succès.']);
    }

    // Activer / Désactiver un agent
    public function toggle(Request $request, $id)
    {
        $agent = User::where('role', 'agent')->findOrFail($id);

        $agent->update(['statut' => !$agent->statut]);

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => $agent->statut ? 'Activation agent' : 'Désactivation agent',
            'details' => 'Agent : ' . $agent->email,
        ]);

        return response()->json([
            'message' => 'Statut mis à jour.',
            'statut'  => $agent->statut,
        ]);
    }

    // Supprimer un agent
    public function destroy(Request $request, $id)
    {
        $agent = User::where('role', 'agent')->findOrFail($id);
        $email = $agent->email;
        $agent->delete();

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Suppression agent',
            'details' => 'Agent supprimé : ' . $email,
        ]);

        return response()->json(['message' => 'Agent supprimé.']);
    }
}