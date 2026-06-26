<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\User;
use Illuminate\Http\Request;

class RechercheController extends Controller
{
    /**
     * Recherche globale multi-entités selon le rôle de l'utilisateur connecté.
     * Retourne utilisateurs (admin), agents (organisateur), et événements.
     */
    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);

        $q    = trim($request->q);
        $user = $request->user();
        $role = $user->role;

        $results = [];

        // ── Événements (commun à tous les rôles sauf participant) ──
        if (in_array($role, ['admin', 'organisateur'])) {
            $evenementQuery = Evenement::where(function ($qr) use ($q) {
                $qr->where('titre', 'like', "%{$q}%")
                   ->orWhere('lieu', 'like', "%{$q}%")
                   ->orWhere('type', 'like', "%{$q}%");
            });

            // Organisateur ne voit que ses propres événements
            if ($role === 'organisateur') {
                $evenementQuery->where('organisateur_id', $user->id);
            }

            $evenements = $evenementQuery->select('id', 'titre', 'lieu', 'date', 'statut', 'type')
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($e) => [
                    'type'    => 'evenement',
                    'id'      => $e->id,
                    'label'   => $e->titre,
                    'sub'     => $e->lieu . ' — ' . \Carbon\Carbon::parse($e->date)->format('d/m/Y'),
                    'statut'  => $e->statut,
                    'url'     => ($role === 'admin' ? '/admin/evenements' : '/organisateur/evenements'),
                ]);

            $results = array_merge($results, $evenements->toArray());
        }

        // ── Utilisateurs (admin uniquement) ──
        if ($role === 'admin') {
            $users = User::where(function ($qr) use ($q) {
                    $qr->where('name', 'like', "%{$q}%")
                       ->orWhere('prenom', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%");
                })
                ->select('id', 'name', 'prenom', 'email', 'role', 'statut')
                ->limit(5)
                ->get()
                ->map(fn($u) => [
                    'type'   => 'utilisateur',
                    'id'     => $u->id,
                    'label'  => trim("{$u->name} {$u->prenom}"),
                    'sub'    => $u->email . ' — ' . ucfirst($u->role),
                    'statut' => $u->statut ? 'actif' : 'inactif',
                    'url'    => '/admin/users',
                ]);

            $results = array_merge($results, $users->toArray());
        }

        // ── Agents (organisateur uniquement) ──
        if ($role === 'organisateur') {
            $agents = User::where('role', 'agent')
                ->where(function ($qr) use ($q) {
                    $qr->where('name', 'like', "%{$q}%")
                       ->orWhere('prenom', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%");
                })
                ->select('id', 'name', 'prenom', 'email', 'statut')
                ->limit(5)
                ->get()
                ->map(fn($u) => [
                    'type'   => 'agent',
                    'id'     => $u->id,
                    'label'  => trim("{$u->name} {$u->prenom}"),
                    'sub'    => $u->email,
                    'statut' => $u->statut ? 'actif' : 'inactif',
                    'url'    => '/organisateur/agents',
                ]);

            $results = array_merge($results, $agents->toArray());
        }

        return response()->json([
            'query'   => $q,
            'results' => $results,
            'count'   => count($results),
        ]);
    }
}
