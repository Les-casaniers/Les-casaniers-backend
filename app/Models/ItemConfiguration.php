<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemConfiguration extends Model
{
    protected $table = 'items_configuration';
    
    protected $fillable = [
        'configuration_id', 'etape_id', 'emplacement', 'produit_id',
        'titre', 'quantite', 'prix_unitaire', 'meta_json',
        'date_creation', 'date_modification'
    ];
    
    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'quantite' => 'integer',
        'meta_json' => 'array',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime'
    ];
    
    // Relation avec la configuration
    public function configuration()
    {
        return $this->belongsTo(Configuration::class);
    }
    
    // Relation avec l'étape (si vous avez la table etapes_configurateur)
    // public function etape()
    // {
    //     return $this->belongsTo(EtapeConfigurateur::class, 'etape_id');
    // }
    
    // Relation avec le produit
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
    
    // Accesseur pour le total de l'item
    public function getTotalAttribute()
    {
        return $this->prix_unitaire * $this->quantite;
    }
}
