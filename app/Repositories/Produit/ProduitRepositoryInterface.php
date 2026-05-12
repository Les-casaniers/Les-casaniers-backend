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
}
