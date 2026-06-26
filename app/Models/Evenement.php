<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    protected $fillable = [
        'organisateur_id', 'titre','type', 'description',
        'image', 'date', 'lieu', 'capacite_max', 'statut',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function organisateur()
    {
        return $this->belongsTo(User::class, 'organisateur_id');
    }

    public function categories()
    {
        return $this->hasMany(CategorieTicket::class, 'evenement_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'evenement_id');
    }

    public function agents()
    {
        return $this->belongsToMany(User::class, 'agent_evenement', 'evenement_id', 'agent_id')
                    ->withPivot('actif', 'date_affectation')
                    ->withTimestamps();
    }

    public function scans()
    {
        return $this->hasMany(ScanQr::class, 'evenement_id');
    }
}