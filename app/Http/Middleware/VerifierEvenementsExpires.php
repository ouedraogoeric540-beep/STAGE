<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Evenement;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class VerifierEvenementsExpires
{
    public function handle(Request $request, Closure $next)
    {
        // Vérifier les événements expirés à chaque requête
        $evenements = Evenement::where('statut', 'actif')
            ->where('date', '<', now())
            ->get();

        foreach ($evenements as $evenement) {
            $evenement->update(['statut' => 'termine']);

            Ticket::where('evenement_id', $evenement->id)
                ->where('statut', 'valide')
                ->update(['statut' => 'expire']);

            DB::table('agent_evenement')
                ->where('evenement_id', $evenement->id)
                ->update(['actif' => false]);
        }

        return $next($request);
    }
}