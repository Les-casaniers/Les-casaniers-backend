<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPanier extends Model
{
    protected $table = 'items_panier';
    
    protected $fillable = [
        'panier_id', 'produit_id', 'configuration_id', 'titre', 
        'prix_unitaire', 'quantite', 'date_creation', 'date_modification'
    ];
    
    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'quantite' => 'integer',
        'configuration_id' => 'integer'
    ];
    
    // Relation avec le panier
    public function panier()
    {
        return $this->belongsTo(Panier::class);
    }
    
    // Relation avec le produit
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
    
    // Calculer le total de l'item
    public function getTotalAttribute()
    {
        return $this->prix_unitaire * $this->quantite;
    }
}