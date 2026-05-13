<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commande extends Model
{
    use HasFactory;

    protected $table = 'commandes';

    protected $fillable = [
        'commande_uuid',
        'utilisateur_id',
        'panier_id',
        'devis_id',
        'statut',
        'sous_total',
        'livraison',
        'total',
        'devise',
        'adresse_expedition_id',
        'adresse_facturation_id',
        'produit_id',
        'titre',
        'reference',
        'prix_unitaire',
        'quantite',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
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

    public function devis()
    {
        return $this->belongsTo(Devis::class);
    }

    public function facture()
    {
        return $this->hasOne(Facture::class, 'commande_id');
    }
}
