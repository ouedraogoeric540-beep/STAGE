<?php

namespace App\Services;

use App\Mail\TicketMail;
use App\Models\Ticket;
use App\Models\NotificationEventsecure;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function envoyerTicket(Ticket $ticket): bool
    {
        $ticket->load(['participant', 'evenement', 'categorie', 'paiement']);

        try {
            $user = \App\Models\User::find($ticket->participant->user_id);
            $prefs = $user ? $user->notif_prefs : null;
            
            // On vérifie la préférence (activé par défaut si non défini)
            $wantsEmail = true;
            if (is_array($prefs) && array_key_exists('ticketPurchased', $prefs)) {
                $wantsEmail = $prefs['ticketPurchased'];
            }

            if ($wantsEmail) {
                Mail::to($ticket->participant->email)
                    ->send(new TicketMail($ticket));
            }

            NotificationEventsecure::create([
                'user_id'    => $ticket->participant->user_id,
                'ticket_id'  => $ticket->id,
                'type'       => 'email',
                'contenu'    => 'Ticket envoyé : ' . $ticket->evenement->titre,
                'statut'     => 'envoye',
                'date_envoi' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur email ticket #' . $ticket->id . ' : ' . $e->getMessage());

            NotificationEventsecure::create([
                'user_id'   => $ticket->participant->user_id,
                'ticket_id' => $ticket->id,
                'type'      => 'email',
                'contenu'   => 'Échec envoi : ' . $ticket->evenement->titre,
                'statut'    => 'echec',
            ]);

            return false;
        }
    }
}