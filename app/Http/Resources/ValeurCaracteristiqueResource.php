<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValeurCaracteristiqueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produit_id' => $this->produit_id,
            'template_id' => $this->template_id,
            'valeur' => $this->valeur,
            'date_creation' => $this->created_at,
            'date_modification' => $this->updated_at,
            // Optionnel
            'template' => new TemplateCaracteristiqueResource($this->whenLoaded('template')),
            'produit' => new ProduitResource($this->whenLoaded('produit')),
        ];
    }
}