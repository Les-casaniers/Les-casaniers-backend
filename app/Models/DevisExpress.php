<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevisExpress extends Model
{
    protected $table = 'devis_express';
    
    protected $fillable = [
        'nom', 'email', 'telephone', 'entreprise', 'besoin',
        'budget', 'date_souhaitee', 'message', 'statut'
    ];
    
    protected $casts = [
        'date_souhaitee' => 'date',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime'
    ];
}