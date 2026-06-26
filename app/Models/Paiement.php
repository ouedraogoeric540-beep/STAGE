<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = [
        'ticket_id', 'montant', 'methode',
        'statut', 'reference', 'date_paiement',
    ];

    protected $casts = [
        'montant'       => 'float',
        'date_paiement' => 'datetime',
    ];

    // Auto-génération référence PAY-XXXXXXXXXX
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($paiement) {
            if (empty($paiement->reference)) {
                $paiement->reference = 'PAY-' . strtoupper(substr(uniqid('', true), 0, 10));
            }
        });
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}