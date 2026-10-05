<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevisExpress extends Model
{
    protected $table = 'devis_express';

    public $timestamps = false;

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    protected $fillable = [
        'nom', 'email', 'telephone', 'entreprise', 'besoin',
        'budget', 'date_souhaitee', 'message', 'statut',
        'date_creation', 'date_modification'
    ];

    protected $casts = [
        'date_souhaitee' => 'date',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime'
    ];
}