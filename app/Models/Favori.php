<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Favori extends Model
{
    use HasFactory;

    protected $table = 'favoris';

    protected $fillable = [
        'utilisateur_id',
        'produit_id',
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = null;

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}