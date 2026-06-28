<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'prenom', 'sexe', 'email', 'password', 'role',
        'statut', 'tentatives_connexion',
        'bloque_jusqu_a', 'telephone', 'notif_prefs',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'statut'            => 'boolean',
        'bloque_jusqu_a'    => 'datetime',
        'notif_prefs'       => 'array',
    ];

    // Relations
    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'organisateur_id');
    }

    public function agentEvenements()
    {
        return $this->belongsToMany(Evenement::class, 'agent_evenement', 'agent_id', 'evenement_id')
                    ->withPivot('actif', 'date_affectation')
                    ->withTimestamps();
    }

    public function scans()
    {
        return $this->hasMany(ScanQr::class, 'agent_id');
    }

    public function notifications()
    {
        return $this->hasMany(NotificationEventsecure::class, 'user_id');
    }

    public function logs()
    {
        return $this->hasMany(LogSysteme::class, 'user_id');
    }

    public function participant()
    {
        return $this->hasOne(Participant::class);
    }
}