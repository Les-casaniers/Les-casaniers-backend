<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'nom' => $this->nom,
            'type' => $this->type,
            'ordre_tri' => $this->ordre_tri,
            'date_creation' => $this->date_creation,
            'date_modification' => $this->date_modification,
        ];
    }
}