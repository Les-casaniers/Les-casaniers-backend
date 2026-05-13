<?php

namespace App\Repositories\AvisClients;

interface AvisClientRepositoryInterface
{
    public function create(array $data);

    public function existsByProduitAndUser(int $produitId, int $utilisateurId): bool;

    public function getPublishedByProduit(int $produitId);

    public function findById(int $id);

    public function findByIdWithRelations(int $id);

    public function getByUser(int $utilisateurId);

    public function update(int $id, array $data);

    public function delete(int $id): bool;

    public function getAdminList(array $filters = []);

    public function getAverageNoteByProduit(int $produitId): float;

    public function getStatsByProduit(int $produitId): array;

    public function search(array $filters = []);

    public function getLatest(int $limit = 10);
}
