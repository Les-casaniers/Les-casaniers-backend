<?php

namespace App\Repositories\Paniers;

interface PanierRepositoryInterface
{
    public function getByUser(int $userId, string $statut = 'actif');

    public function findItem(array $data);

    public function findByIdForUser(int $itemId, int $userId, string $statut = 'actif');

    public function create(array $data);

    public function updateQuantity(int $id, int $quantity);

    public function deleteItem(int $id);

    public function clearCart(int $userId);
}
