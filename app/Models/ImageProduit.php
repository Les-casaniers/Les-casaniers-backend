<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageProduit extends Model
{
    protected $table = 'images_produits';

    protected $fillable = [
        'produit_id',
        'url',
        'alt',
        'ordre'
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function getFilenameAttribute(): string
    {
        return basename($this->url);
    }
}