<?php

namespace App\Repositories\AttributProduit;

interface AttributProduitRepositoryInterface
{
    public function findById(int $id);
    public function findByProduit(int $produitId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function deleteByProduit(int $produitId);
}
