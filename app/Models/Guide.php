<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Guide extends Model
{
    use HasFactory;

    protected $table = 'guides';

    protected $fillable = [
        'titre',
        'slug',
        'resume',
        'contenu',
        'categorie',
        'statut',
        'badge',
        'budget_min',
        'budget_max',
        'composants_recommandes',
        'niveau',
        'difficulte',
        'duree',
        'etapes',
        'video_url',
        'tags',
        'ordre',
        'mis_en_avant',
        'image_url',
        'image_alt',
        'auteur',
        'temps_lecture',
        'popularite',
        'vues',
        'publie_le',
    ];

    protected $casts = [
        'popularite' => 'integer',
        'vues' => 'integer',
        'ordre' => 'integer',
        'mis_en_avant' => 'boolean',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'composants_recommandes' => 'array',
        'etapes' => 'array',
        'tags' => 'array',
        'publie_le' => 'datetime',
    ];

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    /**
     * Auto-generate slug from titre on create/update if not provided.
     */
    protected static function booted(): void
    {
        static::saving(function (Guide $guide) {
            if (empty($guide->slug) && !empty($guide->titre)) {
                $base = Str::slug($guide->titre);
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->where('id', '!=', $guide->id ?? 0)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $guide->slug = $slug;
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('statut', 'publie');
    }

    public function scopeByCategory(Builder $query, string $categorie): Builder
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('mis_en_avant', true);
    }

    /**
     * Format budget range for display (e.g. "1 200 000 Ar – 2 500 000 Ar").
     */
    public function getBudgetRangeAttribute(): ?string
    {
        if (!$this->budget_min && !$this->budget_max) {
            return null;
        }

        $fmt = fn($v) => number_format((float) $v, 0, ',', ' ') . ' Ar';

        if ($this->budget_min && $this->budget_max) {
            return $fmt($this->budget_min) . ' – ' . $fmt($this->budget_max);
        }

        return $this->budget_min ? 'À partir de ' . $fmt($this->budget_min) : 'Jusqu\'à ' . $fmt($this->budget_max);
    }

    protected $appends = ['budget_range'];
}
