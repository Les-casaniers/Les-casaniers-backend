<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'nom',
        'type',
        'ordre_tri'
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function enfants()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function produits()
    {
        return $this->hasMany(Produit::class, 'categorie_id');
    }
}
