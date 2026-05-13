<?php

namespace App\Repositories\Favoris;

use App\Models\Favori;

class FavoriRepository implements FavoriRepositoryInterface
{
    public function getAllByUser(int $utilisateurId)
    {
        return Favori::with('produit')
            ->where('utilisateur_id', $utilisateurId)
            ->latest('id')
            ->get();
    }

    public function findByUserAndProduit(
        int $utilisateurId,
        int $produitId
    ) {
        return Favori::where('utilisateur_id', $utilisateurId)
            ->where('produit_id', $produitId)
            ->first();
    }

    public function create(array $data)
    {
        return Favori::create($data);
    }

    public function delete(int $utilisateurId, int $produitId)
    {
        return Favori::where('utilisateur_id', $utilisateurId)
            ->where('produit_id', $produitId)
            ->delete();
    }

    public function exists(
        int $utilisateurId,
        int $produitId
    ): bool {
        return Favori::where('utilisateur_id', $utilisateurId)
            ->where('produit_id', $produitId)
            ->exists();
    }
}