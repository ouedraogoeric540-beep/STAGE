<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = [
        'nom', 'prenom', 'sexe', 'email', 'telephone', 'a_compte', 'user_id',
    ];

    protected $casts = [
        'a_compte' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}