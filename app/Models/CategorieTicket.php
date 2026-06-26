<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorieTicket extends Model
{
    protected $table = 'categories_tickets';

    protected $fillable = [
        'evenement_id', 'nom', 'prix',
        'quantite_total', 'quantite_vendue',
    ];

    protected $casts = [
        'prix'             => 'float',
        'quantite_total'   => 'integer',
        'quantite_vendue'  => 'integer',
    ];

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'categorie_id');
    }

    // Places restantes
    public function getPlacesRestantesAttribute(): int
    {
        return $this->quantite_total - $this->quantite_vendue;
    }

    public function estDisponible(): bool
    {
        return $this->places_restantes > 0;
    }
}