<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanQr extends Model
{
    protected $table = 'scans_qr';

    protected $fillable = [
        'qr_code', 'ticket_id', 'agent_id',
        'evenement_id', 'resultat', 'date_scan',
    ];

    protected $casts = [
        'date_scan' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }
}