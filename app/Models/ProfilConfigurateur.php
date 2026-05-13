<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilConfigurateur extends Model
{
    protected $table = 'profils_configurateur';
    
    protected $fillable = [
        'nom', 'slug', 'description', 'emplacements', 'actif'
    ];
    
    protected $casts = [
        'emplacements' => 'array',
        'actif' => 'boolean'
    ];
    
    public function configurations()
    {
        return $this->hasMany(Configuration::class, 'profil_id');
    }
}
