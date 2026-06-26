<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'code_unique', 'participant_id', 'evenement_id',
        'categorie_id', 'qr_code', 'statut',
        'prix_paye', 'pdf_path',
    ];

    protected $casts = [
        'prix_paye' => 'float',
    ];

    // Auto-génération code_unique et qr_code à la création
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->code_unique)) {
                $evenement = \App\Models\Evenement::find($ticket->evenement_id);
                $categorie = \App\Models\CategorieTicket::find($ticket->categorie_id);

                $typeStr = $evenement && $evenement->type ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $evenement->type), 0, 4)) : 'EVNT';
                $catStr = $categorie && $categorie->nom ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $categorie->nom), 0, 4)) : 'TICK';
                $dateStr = date('Ymd');
                $timeStr = date('His');
                $rand = strtoupper(\Illuminate\Support\Str::random(3));

                // Format: SP-TYPE-CAT-DATE-TIME-RANDOM
                $ticket->code_unique = "SP-{$typeStr}-{$catStr}-{$dateStr}-{$timeStr}-{$rand}";
            }
            if (empty($ticket->qr_code)) {
                $ticket->qr_code = hash('sha256', $ticket->code_unique . uniqid('', true));
            }
        });
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieTicket::class, 'categorie_id');
    }

    public function paiement()
    {
        return $this->hasOne(Paiement::class);
    }

    public function scans()
    {
        return $this->hasMany(ScanQr::class);
    }

    public function notifications()
    {
        return $this->hasMany(NotificationEventsecure::class);
    }
}