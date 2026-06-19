<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageProduit extends Model
{
    protected $table = 'images_produits';

    // Désactiver les timestamps automatiques (created_at, updated_at)
    public $timestamps = false;

    protected $fillable = [
        'produit_id',
        'url',
        'alt',
        'ordre'
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    // Utiliser 'date_creation' comme date de création
    const CREATED_AT = 'date_creation';
    // Pas de updated_at
    const UPDATED_AT = null;

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function getFilenameAttribute(): string
    {
        return basename($this->url);
    }
}