<?php

namespace App\Repositories\Produit;

interface ProduitRepositoryInterface
{
    public function getAll(array $filters = []);
    public function findById(int $id);
    public function findByCategory(int $categoryId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function search(string $term);
    public function getLatest(int $limit = 10);
    public function getLastReference(): ?string;
    public function getLastReferenceByPrefix(string $prefix): ?string;

    /**
     * Décrémente atomiquement le stock d'un produit.
     * Utilise une clause WHERE pour éviter les stocks négatifs.
     *
     * @return bool true si la mise à jour a eu lieu, false si stock insuffisant.
     */
    public function decrementStock(int $id, int $quantity): bool;

    /**
     * Incrémente le stock d'un produit (restauration après annulation/remboursement).
     */
    public function incrementStock(int $id, int $quantity): void;
}
