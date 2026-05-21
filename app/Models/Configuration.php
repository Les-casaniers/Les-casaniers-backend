<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Factories\HasFactory;

// class Configuration extends Model
// {
//     use HasFactory;

//     protected $table = 'configurations';

//     protected $fillable = [
//         'produit_id',
//         'utilisateur_id',
//         'nom_configuration',
//         'nom_configuration_autre',
//         'devise',
//         'prix_total',
//         'composants_json',
//     ];

//     protected $casts = [
//         'composants_json' => 'array',
//     ];

//     const CREATED_AT = 'date_creation';
//     const UPDATED_AT = 'date_modification';

//     public function produit()
//     {
//         return $this->belongsTo(Produit::class);
//     }

//     public function utilisateur()
//     {
//         return $this->belongsTo(Utilisateur::class);
//     }
// }

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table = 'configurations';
    
    protected $fillable = [
        'produit_id',
        'utilisateur_id',
        'nom_configuration',
        'nom_configuration_autre',
        'devise',
        'prix_total',
        'composants_json',
    ];
    
    protected $casts = [
        'composants_json' => 'array',  // ← Ceci est crucial !
        'prix_total' => 'decimal:2',
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';
    
    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
    
    public function utilisateur()
    {
        return $this->belongsTo(utilisateur::class, 'utilisateur_id');
    }
}