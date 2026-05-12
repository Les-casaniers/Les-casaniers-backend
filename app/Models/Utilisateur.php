<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'telephone',
        'mot_de_passe',
        'statut'
    ];

    protected $hidden = [
        'mot_de_passe'
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    /**
     * Hash le mot de passe avant de l'enregistrer.
     */
    public function setMotDePasseAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['mot_de_passe'] = bcrypt($value);
        }
    }

    /**
     * Retourne le mot de passe pour l'authentification.
     */
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }
}