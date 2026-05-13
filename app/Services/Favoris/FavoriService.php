<?php

namespace App\Services\Favoris;

use App\Repositories\Favoris\FavoriRepositoryInterface;
use Illuminate\Validation\ValidationException;

class FavoriService
{
    public function __construct(
        private readonly FavoriRepositoryInterface $favoriRepository
    ) {
    }

    public function listByUser(int $utilisateurId)
    {
        return $this->favoriRepository->getAllByUser($utilisateurId);
    }

    public function addFavori(int $utilisateurId, int $produitId)
    {
        if ($this->favoriRepository->exists($utilisateurId, $produitId)) {
            throw ValidationException::withMessages([
                'produit_id' => ['Ce produit est déjà dans vos favoris.'],
            ]);
        }

        return $this->favoriRepository->create([
            'utilisateur_id' => $utilisateurId,
            'produit_id' => $produitId,
        ]);
    }

    public function removeFavori(int $utilisateurId, int $produitId): bool
    {
        return $this->favoriRepository->delete($utilisateurId, $produitId) > 0;
    }
}
