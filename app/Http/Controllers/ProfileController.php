<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Participant;
use App\Models\LogSysteme;

class ProfileController extends Controller
{
    /**
     * Mettre à jour les informations du profil
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'telephone' => 'nullable|string|max:20',
        ]);

        // Mise à jour de l'utilisateur
        $user->update([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'sexe' => $request->sexe,
            'email' => $request->email,
            'telephone' => $request->telephone,
        ]);

        // Si l'utilisateur est un participant, on met aussi à jour la table participants
        if ($user->role === 'participant') {
            $participant = Participant::where('user_id', $user->id)->first();
            if ($participant) {
                $participant->update([
                    'nom' => $request->name,
                    'prenom' => $request->prenom,
                    'sexe' => $request->sexe,
                    'email' => $request->email,
                    'telephone' => $request->telephone,
                ]);
            }
        }

        LogSysteme::create([
            'user_id'    => $user->id,
            'action'     => 'Mise à jour profil',
            'ip_address' => $request->ip(),
            'details'    => 'L\'utilisateur a mis à jour ses informations de profil.',
        ]);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user
        ]);
    }

    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'errors' => ['current_password' => ['Le mot de passe actuel est incorrect.']]
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        LogSysteme::create([
            'user_id'    => $user->id,
            'action'     => 'Modification mot de passe',
            'ip_address' => $request->ip(),
            'details'    => 'L\'utilisateur a modifié son mot de passe.',
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès'
        ]);
    }

    /**
     * Mettre à jour les préférences de notification
     */
    public function updateNotifPrefs(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'ticketPurchased' => 'boolean',
            'agentAssigned' => 'boolean',
            'securityAlert' => 'boolean',
        ]);

        $prefs = $user->notif_prefs ?? [
            'ticketPurchased' => true,
            'agentAssigned' => true,
            'securityAlert' => true,
        ];

        // Mettre à jour avec les nouvelles valeurs si elles sont fournies
        if ($request->has('ticketPurchased')) $prefs['ticketPurchased'] = $request->ticketPurchased;
        if ($request->has('agentAssigned')) $prefs['agentAssigned'] = $request->agentAssigned;
        if ($request->has('securityAlert')) $prefs['securityAlert'] = $request->securityAlert;

        $user->update([
            'notif_prefs' => $prefs
        ]);

        return response()->json([
            'message' => 'Préférences de notifications mises à jour',
            'notif_prefs' => $prefs,
            'user' => $user
        ]);
    }
}
