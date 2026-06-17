<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'admin';

    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'telephone',
        'mot_de_passe',
        'poste',
        'statut',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    public function setMotDePasseAttribute($value)
    {
        $this->attributes['mot_de_passe'] = bcrypt($value);
    }

    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }
}
