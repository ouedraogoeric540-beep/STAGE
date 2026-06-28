<?php

namespace App\Http\Controllers;

use App\Models\NotificationEventsecure;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Récupérer les notifications de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = NotificationEventsecure::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->map(function ($n) {
                // Essayer de récupérer le nom de l'événement si le contenu le mentionne ou via le contexte
                // On va parser le titre depuis le contenu ou utiliser "Alerte système"
                $titre = $n->type === 'alerte' ? 'Alerte Agent' : 'Système';
                if ($n->type === 'alerte' && strpos($n->contenu, 'Événement ID:') !== false) {
                    $titre = 'Alerte Urgence';
                }

                return [
                    'id' => $n->id,
                    'type' => $n->type ?? 'info',
                    'title' => $titre,
                    'message' => $n->contenu,
                    'is_read' => $n->statut === 'lu',
                    'created_at' => $n->created_at,
                    'evenement' => null // Peut être ajouté si on lie un ticket ou un id d'événement
                ];
            });

        return response()->json($notifications);
    }

    /**
     * Marquer toutes les notifications non lues comme lues
     */
    public function markAsRead(Request $request)
    {
        $user = $request->user();

        NotificationEventsecure::where('user_id', $user->id)
            ->where('statut', '!=', 'lu')
            ->update(['statut' => 'lu']);

        return response()->json(['message' => 'Notifications marquées comme lues.']);
    }
}
