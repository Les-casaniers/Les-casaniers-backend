<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateCaracteristiqueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sous_categorie_id' => $this->sous_categorie_id,
            'nom_champ' => $this->nom_champ,
            'type_champ' => $this->type_champ,
            'ordre_affichage' => $this->ordre_affichage,
            'est_obligatoire' => (bool) $this->est_obligatoire,
            'valeur_par_defaut' => $this->valeur_par_defaut,
            'date_creation' => $this->created_at,
            'date_modification' => $this->updated_at,
            // Optionnel
            'sous_categorie' => new SousCategorieResource($this->whenLoaded('sousCategorie')),
        ];
    }
}