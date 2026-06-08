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
        'nom',
        'description_courte',
        'description',
        'atout',
        'type_produit',
        'prix',
        'devise',
        'quantite_stock',
        'est_dispo',
        'actif'
    ];

    protected $casts = [
        'actif' => 'boolean',
        'est_dispo' => 'boolean',
        'quantite_stock' => 'integer',
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

    public function configurations()
    {
        return $this->hasMany(Configuration::class, 'produit_id');
    }

    // Mutateur pour éviter les stocks négatifs
    public function setQuantiteStockAttribute($value)
    {
        $this->attributes['quantite_stock'] = max(0, $value);
    }
}
