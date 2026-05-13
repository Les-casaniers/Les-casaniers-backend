<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdresseUtilisateur extends Model
{
    protected $table = 'adresses_utilisateurs';
    
    protected $fillable = [
        'utilisateur_id', 'etiquette', 'nom_complet', 'telephone',
        'adresse_ligne1', 'adresse_ligne2', 'ville', 'region',
        'code_postal', 'pays', 'par_defaut_expedition',
        'par_defaut_facturation', 'date_creation', 'date_modification'
    ];
    
    protected $casts = [
        'par_defaut_expedition' => 'boolean',
        'par_defaut_facturation' => 'boolean',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime'
    ];
    
    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }
    
    // Accesseur pour l'adresse complète
    public function getAdresseCompleteAttribute()
    {
        $adresse = $this->adresse_ligne1;
        if ($this->adresse_ligne2) {
            $adresse .= ', ' . $this->adresse_ligne2;
        }
        $adresse .= ', ' . $this->ville;
        if ($this->region) {
            $adresse .= ', ' . $this->region;
        }
        if ($this->code_postal) {
            $adresse .= ' (' . $this->code_postal . ')';
        }
        $adresse .= ', ' . $this->pays;
        return $adresse;
    }
}
