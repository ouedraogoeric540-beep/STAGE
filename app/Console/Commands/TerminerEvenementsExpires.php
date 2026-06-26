<?php

namespace App\Console\Commands;

use App\Models\Evenement;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TerminerEvenementsExpires extends Command
{
    protected $signature   = 'eventsecure:terminer-evenements';
    protected $description = 'Termine les événements expirés et expire les tickets';

    public function handle(): void
    {
        $maintenant = now();

        $evenements = Evenement::where('statut', 'actif')
            ->where('date', '<', $maintenant)
            ->get();

        if ($evenements->isEmpty()) {
            $this->info('Aucun événement à terminer.');
            return;
        }

        foreach ($evenements as $evenement) {
            $evenement->update(['statut' => 'termine']);

            $ticketsExpires = Ticket::where('evenement_id', $evenement->id)
                ->where('statut', 'valide')
                ->update(['statut' => 'expire']);

            DB::table('agent_evenement')
                ->where('evenement_id', $evenement->id)
                ->update(['actif' => false]);

            Log::info('Événement terminé : ' . $evenement->titre);
            $this->info('Terminé : ' . $evenement->titre);
        }

        $this->info('Total : ' . $evenements->count() . ' événement(s) traité(s).');
    }
}