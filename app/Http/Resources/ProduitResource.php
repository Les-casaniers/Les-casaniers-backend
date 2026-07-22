<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProduitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Récupérer les caractéristiques formatées
        $caracteristiques = [];
        foreach ($this->valeursCaracteristiques as $valeur) {
            $caracteristiques[$valeur->template->nom_champ] = $valeur->valeur;
        }

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'nom' => $this->nom,
            'prix' => (float) $this->prix,
            'devise' => $this->devise,
            'en_stock' => $this->quantite_stock > 0,
            'quantite_stock' => (int) $this->quantite_stock,
            'est_dispo' => (bool) $this->est_dispo,
            'description_courte' => $this->description_courte,
            
            // Caractéristiques formatées en clé/valeur
            'caracteristiques' => $caracteristiques,
            
            // Relations simplifiées
            'sous_categorie' => $this->sousCategorie ? [
                'id' => $this->sousCategorie->id,
                'nom' => $this->sousCategorie->nom
            ] : null,
            
            'categorie' => $this->categorie ? [
                'id' => $this->categorie->id,
                'nom' => $this->categorie->nom
            ] : null,
        ];
    }
}