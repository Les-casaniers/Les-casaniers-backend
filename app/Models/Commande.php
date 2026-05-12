<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $table = 'commandes';
    
    protected $fillable = [
        'utilisateur_id', 'statut', 'sous_total', 'livraison', 
        'total', 'devise', 'adresse_livraison', 'adresse_facturation', 
        'notes', 'date_creation', 'date_modification'
    ];
    
    protected $casts = [
        'sous_total' => 'decimal:2',
        'livraison' => 'decimal:2',
        'total' => 'decimal:2',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime',
    ];
    
    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }
    
    // Relation avec les lignes
    public function lignes()
    {
        return $this->hasMany(CommandeLigne::class, 'commande_id');
    }
    
    // Accesseur pour le libellé du statut
    public function getStatutLabelAttribute()
    {
        return match($this->statut) {
            'en_attente' => 'En attente',
            'payee' => 'Payée',
            'en_traitement' => 'En traitement',
            'expediee' => 'Expédiée',
            'terminee' => 'Terminée',
            'annulee' => 'Annulée',
            'remboursee' => 'Remboursée',
            default => $this->statut,
        };
    }
}