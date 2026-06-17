<?php

namespace App\Models;

use App\Enums\Sales\CommandeStatut;
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
        'statut' => CommandeStatut::class,
        'meta_json' => 'array',
        'sous_total' => 'decimal:2',
        'livraison' => 'decimal:2',
        'total' => 'decimal:2',
        'prix_unitaire' => 'decimal:2',
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

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function facture()
    {
        return $this->hasOne(Facture::class, 'commande_id');
    }

    // ✅ RELATION AVEC L'ADRESSE DE LIVRAISON (EXPÉDITION)
    public function adresseExpédition()
    {
        return $this->belongsTo(AdresseUtilisateur::class, 'adresse_expedition_id');
    }

    // ✅ RELATION AVEC L'ADRESSE DE FACTURATION
    public function adresseFacturation()
    {
        return $this->belongsTo(AdresseUtilisateur::class, 'adresse_facturation_id');
    }

    // ✅ MÉTHODE POUR OBTENIR L'ADRESSE FORMATÉE
    public function getAdresseLivraisonFormatted(): string
    {
        // Essayer d'abord avec la relation adresseExpédition
        if ($this->adresseExpédition) {
            return $this->adresseExpédition->getFullAddress();
        }

        // Sinon, essayer de récupérer depuis meta_json
        if ($this->meta_json) {
            $meta = $this->meta_json;
            if (isset($meta['adresse_livraison'])) {
                return $meta['adresse_livraison'];
            }
            if (isset($meta['adresse'])) {
                return $meta['adresse'];
            }
            if (isset($meta['adresse_expedition'])) {
                return $meta['adresse_expedition'];
            }
        }

        return 'Adresse non disponible';
    }
}
