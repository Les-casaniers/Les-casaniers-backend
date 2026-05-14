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

    /**
     * Récupère les items actifs du panier avec la relation produit chargée.
     */
    public function getActiveItemsWithProduit(int $userId);

    /**
     * Marque tous les items actifs d'un utilisateur comme 'commande'.
     */
    public function markActiveAsCommande(int $userId): int;

    /**
     * Trouve un item spécifique du panier par ID pour un utilisateur donné (tout statut).
     */
    public function findById(int $id, int $userId);
}
