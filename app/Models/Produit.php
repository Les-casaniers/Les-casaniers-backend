<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Produit extends Model
{
    use HasFactory;

    protected $table = 'produits';

    protected $fillable = [
        'categorie_id',
        'id_sous_categorie',
        'reference',
        'nom',
        'description_courte',
        'description',
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

    public function sousCategorie()
    {
        return $this->belongsTo(SousCategorie::class, 'id_sous_categorie');
    }

     /**
     * Relation avec les valeurs des caractéristiques
     */
    public function valeursCaracteristiques(): HasMany
    {
        return $this->hasMany(ValeurCaracteristique::class, 'produit_id');
    }
    
    /**
     * Relation avec les templates via les valeurs
     */
    public function templatesCaracteristiques(): BelongsToMany
    {
        return $this->belongsToMany(
            TemplateCaracteristique::class,
            'valeurs_caracteristiques',
            'produit_id',
            'template_id'
        )->withPivot('valeur')->withTimestamps();
    }
    
    /**
     * Récupérer les caractéristiques formatées
     */
    public function getCaracteristiquesAttribute()
    {
        return $this->valeursCaracteristiques()
                    ->with('template')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->template->nom_champ => $item->valeur];
                    });
    }
    
    /**
     * Vérifier si le produit a des caractéristiques
     */
    public function hasCaracteristiques(): bool
    {
        return $this->valeursCaracteristiques()->exists();
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
