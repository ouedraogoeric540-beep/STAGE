<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogSysteme extends Model
{
    protected $table = 'logs_systeme';

    protected $fillable = [
        'user_id', 'action', 'ip_address', 'details',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}