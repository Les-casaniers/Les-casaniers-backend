<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentementCookie extends Model
{
    protected $table = 'consentements_cookies';
    
    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'choix',
        'timestamp'
    ];
    
    protected $casts = [
        'timestamp' => 'datetime',
    ];
}