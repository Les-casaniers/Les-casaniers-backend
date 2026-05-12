<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produit extends Model
{
    use HasFactory;

    protected $table = 'produits';

    protected $fillable = [
        'categorie_id',
        'reference',
        'slug',
        'nom',
        'description_courte',
        'description',
        'type_produit',
        'prix',
        'devise',
        'quantite_stock',
        'actif'
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    public function categorie()
    {
        return $this->belongsTo(Category::class, 'categorie_id');
    }

    public function images()
    {
        return $this->hasMany(ImageProduit::class, 'produit_id');
    }

    public function attributs()
    {
        return $this->hasMany(AttributProduit::class, 'produit_id');
    }
}