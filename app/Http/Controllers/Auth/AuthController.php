<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogSysteme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\CompteBloqueMail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:191',
            'prenom'    => 'nullable|string|max:191',
            'sexe'      => 'nullable|in:M,F',
            'email'     => 'required|email|max:191|unique:users',
            'password'  => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:191',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'prenom'    => $request->prenom,
            'sexe'      => $request->sexe,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'organisateur',
            'statut'    => true,
            'telephone' => $request->telephone,
        ]);

        $token = $user->createToken('eventsecure')->plainTextToken;

        LogSysteme::create([
            'user_id'    => $user->id,
            'action'     => 'Inscription organisateur',
            'ip_address' => $request->ip(),
            'details'    => 'Nouvel organisateur : ' . $user->email,
        ]);

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Utilisateur introuvable
        if (!$user) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        // Compte désactivé
        if (!$user->statut) {
            return response()->json(['message' => 'Votre compte a été désactivé.'], 403);
        }

        // Compte bloqué temporairement
        if ($user->bloque_jusqu_a && Carbon::now()->lt($user->bloque_jusqu_a)) {
            $minutes = (int) Carbon::now()->diffInMinutes($user->bloque_jusqu_a);
            return response()->json([
                'message'           => 'Compte bloqué. Réessayez dans ' . $minutes . ' minute(s).',
                'compte_bloque'     => true,
                'bloque_jusqu_a'    => $user->bloque_jusqu_a,
                'minutes_restantes' => $minutes,
            ], 403);
        }

        // Mot de passe incorrect
        if (!Hash::check($request->password, $user->password)) {
            $tentatives = $user->tentatives_connexion + 1;

            if ($tentatives >= 3) {
                $bloqueJusqua = Carbon::now()->addHours(2);
                $user->update([
                    'tentatives_connexion' => 0,
                    'bloque_jusqu_a'       => $bloqueJusqua,
                ]);

                // Envoyer l'email d'alerte de blocage
                Mail::to($user->email)->send(new CompteBloqueMail($user, $bloqueJusqua));

                LogSysteme::create([
                    'user_id' => $user->id,
                    'action'  => 'Compte bloqué',
                    'details' => 'Compte bloqué pour 2h après 3 tentatives échouées : ' . $user->email,
                    'ip_address' => $request->ip(),
                ]);

                return response()->json([
                    'message'        => 'Compte bloqué pendant 2 heures suite à 3 tentatives échouées.',
                    'compte_bloque'  => true,
                    'bloque_jusqu_a' => $bloqueJusqua,
                ], 403);
            }

            $user->update(['tentatives_connexion' => $tentatives]);

            return response()->json([
                'message'              => 'Identifiants incorrects.',
                'tentatives_restantes' => 3 - $tentatives,
            ], 401);
        }

        // Connexion réussie — réinitialiser les tentatives
        $user->update([
            'tentatives_connexion' => 0,
            'bloque_jusqu_a'       => null,
        ]);

        $token = $user->createToken('eventsecure')->plainTextToken;

        LogSysteme::create([
            'user_id'    => $user->id,
            'action'     => 'Connexion',
            'ip_address' => $request->ip(),
            'details'    => 'Connexion réussie : ' . $user->email,
        ]);

        if ($user->role === 'participant') {
            \App\Models\Participant::where('email', $user->email)
                ->update(['user_id' => $user->id, 'a_compte' => true]);
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        LogSysteme::create([
            'user_id'    => $user->id,
            'action'     => 'Déconnexion',
            'ip_address' => $request->ip(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

public function registerParticipant(Request $request)
{
    $request->validate([
        'name'      => 'required|string|max:191',
        'prenom'    => 'nullable|string|max:191',
        'sexe'      => 'nullable|in:M,F',
        'email'     => 'required|email|max:191|unique:users',
        'password'  => 'required|string|min:8|confirmed',
        'telephone' => 'nullable|string|max:191',
    ]);

    $user = User::create([
        'name'      => $request->name,
        'prenom'    => $request->prenom,
        'sexe'      => $request->sexe,
        'email'     => $request->email,
        'password'  => Hash::make($request->password),
        'role'      => 'participant',
        'statut'    => true,
        'telephone' => $request->telephone,
    ]);

    // Lier le participant existant si même email (fix de sécurité inconditionnel)
    \App\Models\Participant::where('email', $request->email)
        ->update([
            'user_id' => $user->id, 
            'a_compte' => true,
            'prenom' => $request->prenom,
            'sexe' => $request->sexe,
            'nom' => $request->name,
        ]);

    $token = $user->createToken('eventsecure')->plainTextToken;

    LogSysteme::create([
        'user_id'    => $user->id,
        'action'     => 'Inscription participant',
        'ip_address' => $request->ip(),
        'details'    => 'Nouveau participant : ' . $user->email,
    ]);

    return response()->json([
        'message' => 'Compte créé avec succès.',
        'token'   => $token,
        'user'    => $user,
    ], 201);
}
}