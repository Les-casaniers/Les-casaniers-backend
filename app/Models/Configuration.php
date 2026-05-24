<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Configuration extends Model
{
    use HasFactory;

    protected $table = 'configurations';

    protected $fillable = [
        'produit_id',
        'nom_configuration',
        'type',
        'detail',
        'capacite',
        'prix_total',
    ];

    protected $casts = [
        'prix_total' => 'decimal:2',
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

}
