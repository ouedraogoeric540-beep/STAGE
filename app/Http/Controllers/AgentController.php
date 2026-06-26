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
            'agent_id'     => 'required|exists:users,id',
            'evenement_id' => 'required|exists:evenements,id',
        ]);

        // Vérifier que l'événement appartient à l'organisateur
        $evenement = \App\Models\Evenement::where('organisateur_id', $request->user()->id)
            ->findOrFail($request->evenement_id);

        $agent = User::where('role', 'agent')->findOrFail($request->agent_id);

        // Vérifier si déjà affecté
        $exists = DB::table('agent_evenement')
            ->where('agent_id', $agent->id)
            ->where('evenement_id', $evenement->id)
            ->exists();

        if ($exists) {
            // Réactiver si inactif
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
            
            // Send email to agent
            Mail::to($agent->email)->send(new AgentAffectationMail($agent, $evenement));
        }

        LogSysteme::create([
            'user_id' => $request->user()->id,
            'action'  => 'Affectation agent',
            'details' => 'Agent #' . $agent->id . ' affecté à : ' . $evenement->titre,
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