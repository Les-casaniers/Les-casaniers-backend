<?php

namespace App\Repositories\ImageProduit;

interface ImageProduitRepositoryInterface
{
    public function findById(int $id);
    public function findByProduit(int $produitId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function updateOrder(int $produitId, array $imageOrders);
    public function findMainImage(int $produitId); // Ajouter cette ligne
    public function deleteByProduit(int $produitId); // Ajouter cette ligne aussi
}
