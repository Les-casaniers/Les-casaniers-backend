<?php
// app/Models/TemplateCaracteristique.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TemplateCaracteristique extends Model
{
    protected $table = 'templates_caracteristiques';
    
    protected $fillable = [
        'sous_categorie_id',
        'nom_champ',
        'type_champ',
        'ordre_affichage',
        'est_obligatoire',
        'valeur_par_defaut'
    ];
    
    protected $casts = [
        'est_obligatoire' => 'boolean',
        'ordre_affichage' => 'integer'
    ];
    
    /**
     * Relation avec la sous-catégorie
     */
    public function sousCategorie(): BelongsTo
    {
        return $this->belongsTo(SousCategorie::class, 'sous_categorie_id');
    }
    
    /**
     * Relation avec les valeurs des caractéristiques
     */
    public function valeursCaracteristiques(): HasMany
    {
        return $this->hasMany(ValeurCaracteristique::class, 'template_id');
    }
    
    /**
     * Relation avec les produits via les valeurs
     */
    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(
            Produit::class,
            'valeurs_caracteristiques',
            'template_id',
            'produit_id'
        )->withPivot('valeur')->withTimestamps();
    }
    
    /**
     * Scope pour les champs obligatoires
     */
    public function scopeObligatoire($query)
    {
        return $query->where('est_obligatoire', true);
    }
    
    /**
     * Scope pour les champs optionnels
     */
    public function scopeOptionnel($query)
    {
        return $query->where('est_obligatoire', false);
    }
    
    /**
     * Scope pour trier par ordre d'affichage
     */
    public function scopeTrie($query)
    {
        return $query->orderBy('ordre_affichage');
    }
}