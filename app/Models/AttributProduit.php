<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributProduit extends Model
{
    use HasFactory;

    protected $table = 'attributs_produits';

    protected $fillable = [
        'produit_id',
        'cle_attr',
        'libelle_attr',
        'valeur_attr'
    ];

    const CREATED_AT = 'date_creation';
    public $timestamps = false;

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}