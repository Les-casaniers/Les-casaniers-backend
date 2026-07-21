<?php
// app/Models/ProduitCaracteristique.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProduitCaracteristique extends Model
{
    protected $table = 'produit_caracteristiques';

    protected $fillable = [
        'produit_id',
        'nom_champ',
        'valeur',
        'type_champ',
        'est_obligatoire',
        'ordre_affichage'
    ];

    protected $casts = [
        'est_obligatoire' => 'boolean',
        'ordre_affichage' => 'integer'
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}