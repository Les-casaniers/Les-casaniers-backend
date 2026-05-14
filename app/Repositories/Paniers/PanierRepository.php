<?php

namespace App\Repositories\Paniers;

use App\Models\Panier;

class PanierRepository implements PanierRepositoryInterface
{
    public function getByUser(int $userId, string $statut = 'actif')
    {
        return Panier::with(['produit', 'configuration'])
            ->where('utilisateur_id', $userId)
            ->where('statut', $statut)
            ->get();
    }

    public function findItem(array $data)
    {
        return Panier::where($data)->first();
    }

    public function findByIdForUser(int $itemId, int $userId, string $statut = 'actif')
    {
        return Panier::where('id', $itemId)
            ->where('utilisateur_id', $userId)
            ->where('statut', $statut)
            ->first();
    }

    public function create(array $data)
    {
        return Panier::create($data);
    }

    public function updateQuantity(int $id, int $quantity)
    {
        return Panier::where('id', $id)
            ->update(['quantite' => $quantity]);
    }

    public function deleteItem(int $id)
    {
        return Panier::where('id', $id)->delete();
    }

    public function clearCart(int $userId)
    {
        return Panier::where('utilisateur_id', $userId)
            ->where('statut', 'actif')
            ->delete();
    }

    public function getActiveItemsWithProduit(int $userId)
    {
        return Panier::with('produit')
            ->where('utilisateur_id', $userId)
            ->where('statut', 'actif')
            ->get();
    }

    public function markActiveAsCommande(int $userId): int
    {
        return Panier::where('utilisateur_id', $userId)
            ->where('statut', 'actif')
            ->update(['statut' => 'commande']);
    }

    public function findById(int $id, int $userId)
    {
        return Panier::where('id', $id)
            ->where('utilisateur_id', $userId)
            ->first();
    }
}
