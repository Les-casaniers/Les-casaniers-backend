<?php
// app/Models/ValeurCaracteristique.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValeurCaracteristique extends Model
{
    protected $table = 'valeurs_caracteristiques';
    
    protected $fillable = [
        'produit_id',
        'template_id',
        'valeur'
    ];
    
    /**
     * Relation avec le produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
    
    /**
     * Relation avec le template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateCaracteristique::class, 'template_id');
    }
}