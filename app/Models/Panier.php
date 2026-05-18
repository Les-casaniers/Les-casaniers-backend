<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Panier extends Model
{
    use HasFactory;

    protected $table = 'paniers';

    protected $fillable = [
        'utilisateur_id',
        'statut',
        'produit_id',
        'configuration_id',
        'titre',
        'prix_unitaire',
        'quantite',
    ];

    // Constantes pour les statuts
    const STATUT_ACTIF = 'actif';      // Panier actif/en cours
    const STATUT_COMMANDE = 'commande'; // Transformé en commande
    const STATUT_PAYE = 'paye';        // Payé

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function configuration()
    {
        return $this->belongsTo(Configuration::class, 'configuration_id');
    }
}
