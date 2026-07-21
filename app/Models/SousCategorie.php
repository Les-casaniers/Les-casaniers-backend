<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Relation avec les templates de caractéristiques
     */
    public function templatesCaracteristiques(): HasMany
    {
        return $this->hasMany(TemplateCaracteristique::class, 'sous_categorie_id')
                    ->orderBy('ordre_affichage');
    }
    
    /**
     * Vérifier si la sous-catégorie a des caractéristiques
     */
    public function hasCaracteristiques(): bool
    {
        return $this->templatesCaracteristiques()->exists();
    }
    
    /**
     * Récupérer les templates obligatoires
     */
    public function templatesObligatoires()
    {
        return $this->templatesCaracteristiques()->where('est_obligatoire', true);
    }

}
