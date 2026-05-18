<?php

namespace App\Services\Paniers;

use App\Models\Panier;
use App\Models\Produit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PanierService
{
    /**
     * Récupérer le panier actif de l'utilisateur
     */
    public function index(int $utilisateurId)
    {
        return Panier::with('produit')
            ->where('utilisateur_id', $utilisateurId)
            ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif' au lieu de 'panier'
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'produit_id' => $item->produit_id,
                    'titre' => $item->titre,
                    'quantite' => $item->quantite,
                    'prix_unitaire' => $item->prix_unitaire,
                    'statut' => $item->statut,
                    'produit' => $item->produit ? [
                        'id' => $item->produit->id,
                        'nom' => $item->produit->nom,
                        'type_produit' => $item->produit->type_produit,
                        'prix' => $item->produit->prix,
                    ] : null,
                ];
            });
    }

    /**
     * Ajouter un article au panier
     */
    public function addItem(int $utilisateurId, array $data)
    {
        $produit = Produit::findOrFail($data['produit_id']);
        $quantite = $data['quantite'] ?? 1;

        // Vérifier si le produit existe déjà dans le panier actif
        $existingItem = Panier::where('utilisateur_id', $utilisateurId)
            ->where('produit_id', $produit->id)
            ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif'
            ->first();

        if ($existingItem) {
            // Mettre à jour la quantité
            $existingItem->quantite += $quantite;
            $existingItem->save();
            
            return $this->index($utilisateurId);
        }

        // Créer un nouvel article
        Panier::create([
            'utilisateur_id' => $utilisateurId,
            'statut' => Panier::STATUT_ACTIF,  // Changé: 'actif'
            'produit_id' => $produit->id,
            'titre' => $produit->nom,
            'prix_unitaire' => $produit->prix,
            'quantite' => $quantite,
        ]);

        return $this->index($utilisateurId);
    }

    /**
     * Modifier la quantité
     */
    public function updateQuantity(int $utilisateurId, int $itemId, int $quantite)
    {
        $panier = Panier::where('id', $itemId)
            ->where('utilisateur_id', $utilisateurId)
            ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif'
            ->first();

        if (!$panier) {
            throw ValidationException::withMessages([
                'item' => ['Article non trouvé dans le panier actif']
            ]);
        }

        $panier->quantite = $quantite;
        $panier->save();

        return $this->index($utilisateurId);
    }

    /**
     * Supprimer un article du panier
     */
    public function removeItem(int $utilisateurId, int $itemId)
    {
        $panier = Panier::where('id', $itemId)
            ->where('utilisateur_id', $utilisateurId)
            ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif'
            ->first();

        if (!$panier) {
            throw ValidationException::withMessages([
                'item' => ['Article non trouvé dans le panier actif']
            ]);
        }

        $panier->delete();

        return $this->index($utilisateurId);
    }

    /**
     * Vider le panier actif
     */
    public function clear(int $utilisateurId)
    {
        Panier::where('utilisateur_id', $utilisateurId)
            ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif'
            ->delete();

        return $this->index($utilisateurId);
    }
}