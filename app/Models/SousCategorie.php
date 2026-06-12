<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SousCategorie extends Model
{
    use HasFactory;

    protected $table = 'sous_categories';

    protected $fillable = [
        'id_categorie',
        'nom',
    ];

    public function categorie()
    {
        return $this->belongsTo(Category::class, 'id_categorie');
    }

    public function produits()
    {
        return $this->hasMany(Produit::class, 'id_sous_categorie');
    }
}
