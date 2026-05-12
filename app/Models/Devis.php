<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devis extends Model
{
    protected $table = 'devis';
    
    protected $fillable = [
        'utilisateur_id', 'panier_id', 'configuration_id', 'statut',
        'nom_client', 'email_client', 'telephone_client', 'note',
        'montant_total', 'devise', 'date_creation', 'date_modification'
    ];
    
    protected $casts = [
        'montant_total' => 'decimal:2',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime'
    ];
    
    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }
    
    // Relation avec le panier
    public function panier()
    {
        return $this->belongsTo(Panier::class);
    }
    
    // Relation avec la configuration (pour PC sur mesure)
    public function configuration()
    {
        return $this->belongsTo(Configuration::class);
    }
    
    // Accesseur pour le libellé du statut
    public function getStatutLabelAttribute()
    {
        $labels = [
            'brouillon' => 'Brouillon',
            'envoye' => 'Envoyé',
            'accepte' => 'Accepté',
            'refuse' => 'Refusé',
            'expire' => 'Expiré'
        ];
        return $labels[$this->statut];
    }
    
    // Accesseur pour la couleur du statut
    public function getStatutColorAttribute()
    {
        $colors = [
            'brouillon' => 'gray',
            'envoye' => 'blue',
            'accepte' => 'green',
            'refuse' => 'red',
            'expire' => 'orange'
        ];
        return $colors[$this->statut];
    }
}
