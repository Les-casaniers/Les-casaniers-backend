<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Panier extends Model
{
    protected $table = 'paniers';
    
    protected $fillable = [
        'utilisateur_id', 'statut', 'date_creation', 'date_modification'
    ];
    
    protected $casts = [
        'date_creation' => 'datetime',
        'date_modification' => 'datetime'
    ];
    
    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }
    
    // Relation avec les items
    public function items()
    {
        return $this->hasMany(ItemPanier::class, 'panier_id');
    }
    
    // Calculer le total du panier
    public function getTotalAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->prix_unitaire * $item->quantite;
        });
    }
    
    // Compter le nombre d'articles
    public function getNbArticlesAttribute()
    {
        return $this->items->sum('quantite');
    }
}
