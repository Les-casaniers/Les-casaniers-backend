<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Devis extends Model
{
    use HasFactory;

    protected $table = 'devis';

    protected $fillable = [
        'utilisateur_id',
        'panier_id',
        'statut',
        'note',
        'montant_total',
        'devise',
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }

    public function panier()
    {
        return $this->belongsTo(Panier::class);
    }
}