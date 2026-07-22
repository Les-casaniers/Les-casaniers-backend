<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SousCategorieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_categorie' => $this->id_categorie,
            'nom' => $this->nom,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Optionnel : inclure la catégorie parente
            'categorie' => new CategorieResource($this->whenLoaded('categorie')),
        ];
    }
}