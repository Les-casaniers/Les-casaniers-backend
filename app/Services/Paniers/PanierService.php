<?php

namespace App\Services\Paniers;

use App\Models\Produit;
use App\Repositories\Paniers\PanierRepositoryInterface;
use Illuminate\Validation\ValidationException;

class PanierService
{
    public function __construct(
        private readonly PanierRepositoryInterface $panierRepository
    ) {
    }

    public function index(int $utilisateurId): array
    {
        $items = $this->panierRepository->getByUser($utilisateurId, 'actif');

        return [
            'items' => $items,
            'total' => $this->calculateTotal($items->all()),
        ];
    }

    public function addItem(int $utilisateurId, array $payload): array
    {
        $produit = Produit::find($payload['produit_id']);
        if (!$produit) {
            throw ValidationException::withMessages([
                'produit_id' => ['Produit introuvable.'],
            ]);
        }

        $existing = $this->panierRepository->findItem([
            'utilisateur_id' => $utilisateurId,
            'statut' => 'actif',
            'produit_id' => $payload['produit_id'],
            'configuration_id' => null,
        ]);

        if ($existing) {
            $newQuantity = (int) $existing->quantite + (int) ($payload['quantite'] ?? 1);
            $this->panierRepository->updateQuantity((int) $existing->id, $newQuantity);
        } else {
            $this->panierRepository->create([
                'utilisateur_id' => $utilisateurId,
                'statut' => 'actif',
                'produit_id' => $produit->id,
                'configuration_id' => null,
                'titre' => $produit->nom,
                'prix_unitaire' => $produit->prix,
                'quantite' => (int) ($payload['quantite'] ?? 1),
            ]);
        }

        return $this->index($utilisateurId);
    }

    public function updateQuantity(int $utilisateurId, int $itemId, int $quantite): array
    {
        $item = $this->panierRepository->findByIdForUser($itemId, $utilisateurId, 'actif');
        if (!$item) {
            throw ValidationException::withMessages([
                'item_id' => ['Article du panier introuvable.'],
            ]);
        }

        $this->panierRepository->updateQuantity($itemId, $quantite);

        return $this->index($utilisateurId);
    }

    public function removeItem(int $utilisateurId, int $itemId): array
    {
        $item = $this->panierRepository->findByIdForUser($itemId, $utilisateurId, 'actif');
        if (!$item) {
            throw ValidationException::withMessages([
                'item_id' => ['Article du panier introuvable.'],
            ]);
        }

        $this->panierRepository->deleteItem($itemId);

        return $this->index($utilisateurId);
    }

    public function clear(int $utilisateurId): array
    {
        $this->panierRepository->clearCart($utilisateurId);

        return [
            'items' => [],
            'total' => 0,
        ];
    }

    public function calculateTotal(array $items): float
    {
        return (float) collect($items)->sum(function ($item) {
            return ((float) ($item->prix_unitaire ?? 0)) * ((int) ($item->quantite ?? 0));
        });
    }
}
