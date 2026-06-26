<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class CheckCompteBloque
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié.',
            ], 401);
        }

        // Vérifier si le compte est désactivé
        if (!$user->statut) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Contactez un administrateur.',
            ], 403);
        }

        // Vérifier si le compte est temporairement bloqué
        if ($user->bloque_jusqu_a && Carbon::now()->lt($user->bloque_jusqu_a)) {
            $minutesRestantes = (int) Carbon::now()->diffInMinutes($user->bloque_jusqu_a);

            return response()->json([
                'message'           => 'Compte temporairement bloqué suite à plusieurs tentatives échouées.',
                'bloque_jusqu_a'    => $user->bloque_jusqu_a,
                'minutes_restantes' => $minutesRestantes,
            ], 403);
        }

        // Si le blocage temporaire est expiré, on réinitialise
        if ($user->bloque_jusqu_a && Carbon::now()->gte($user->bloque_jusqu_a)) {
            $user->update([
                'bloque_jusqu_a'       => null,
                'tentatives_connexion' => 0,
            ]);
        }

        return $next($request);
    }
}