<?php
// app/Models/AdresseUtilisateur.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdresseUtilisateur extends Model
{
    protected $table = 'adresses_utilisateurs';
    
    // ✅ Activer les timestamps automatiques de Laravel
    public $timestamps = true;
    
    // ✅ Définir les noms des colonnes de timestamps
    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';
    
    protected $fillable = [
        'utilisateur_id',
        'etiquette',
        'nom_complet',
        'telephone',
        'adresse_ligne1',
        'adresse_ligne2',
        'ville',
        'region',
        'code_postal',
        'pays',
        'par_defaut_expedition',
        'par_defaut_facturation',
        'image_adress',
        'latitude',
        'longitude',
        'date_creation',
        'date_modification'
    ];
    
    protected $casts = [
        'par_defaut_expedition' => 'boolean',
        'par_defaut_facturation' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime',
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

    public function getFullAddress(): string
    {
        $parts = [];

        if ($this->nom_complet) {
            $parts[] = $this->nom_complet;
        }
        if ($this->adresse_ligne1) {
            $parts[] = $this->adresse_ligne1;
        }
        if ($this->adresse_ligne2) {
            $parts[] = $this->adresse_ligne2;
        }

        $cityParts = [];
        if ($this->code_postal) {
            $cityParts[] = $this->code_postal;
        }
        if ($this->ville) {
            $cityParts[] = $this->ville;
        }
        if (!empty($cityParts)) {
            $parts[] = implode(' ', $cityParts);
        }

        if ($this->region) {
            $parts[] = $this->region;
        }
        if ($this->pays) {
            $parts[] = $this->pays;
        }

        return !empty($parts) ? implode(', ', $parts) : 'Adresse non disponible';
    }

    public function getAddressLine(): string
    {
        $parts = [];

        if ($this->adresse_ligne1) {
            $parts[] = $this->adresse_ligne1;
        }
        if ($this->adresse_ligne2) {
            $parts[] = $this->adresse_ligne2;
        }
        if ($this->code_postal) {
            $parts[] = $this->code_postal;
        }
        if ($this->ville) {
            $parts[] = $this->ville;
        }

        return !empty($parts) ? implode(' ', $parts) : 'Adresse non disponible';
    }
}