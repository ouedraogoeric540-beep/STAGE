<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationEventsecure extends Model
{
    protected $table = 'notifications_eventsecure';

    protected $fillable = [
        'user_id', 'ticket_id', 'type',
        'contenu', 'statut', 'date_envoi',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}