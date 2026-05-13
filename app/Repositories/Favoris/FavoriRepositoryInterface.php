<?php

namespace App\Repositories\Favoris;

interface FavoriRepositoryInterface
{
    public function getAllByUser(int $utilisateurId);

    public function findByUserAndProduit(
        int $utilisateurId,
        int $produitId
    );

    public function create(array $data);

    public function delete(int $utilisateurId, int $produitId);

    public function exists(int $utilisateurId, int $produitId): bool;
}