<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table = 'configurations';
    
    protected $fillable = [
        'utilisateur_id', 'profil_id', 'nom', 'statut', 
        'prix_total', 'devise', 'date_creation', 'date_modification'
    ];
    
    protected $casts = [
        'prix_total' => 'decimal:2',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime'
    ];
    
    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }
    
    // Relation avec le profil configurateur
    public function profil()
    {
        return $this->belongsTo(ProfilConfigurateur::class, 'profil_id');
    }
    
    // Relation avec les items de configuration
    public function items()
    {
        return $this->hasMany(ItemConfiguration::class, 'configuration_id');
    }
    
    // Accesseur pour le libellé du statut
    public function getStatutLabelAttribute()
    {
        $labels = [
            'brouillon' => 'Brouillon',
            'pret' => 'Prêt',
            'devis' => 'Devis',
            'commande' => 'Commandé'
        ];
        return $labels[$this->statut] ?? $this->statut;
    }
    
    // Accesseur pour la couleur du statut
    public function getStatutColorAttribute()
    {
        $colors = [
            'brouillon' => 'gray',
            'pret' => 'green',
            'devis' => 'blue',
            'commande' => 'purple'
        ];
        return $colors[$this->statut] ?? 'gray';
    }
    
    // Calculer le prix total
    public function getPrixTotalAttribute()
    {
        return $this->items->sum(function($item) {
            return $item->prix_unitaire * $item->quantite;
        });
    }
}