<?php

// namespace App\Services\Paniers;

// use App\Models\Panier;
// use App\Models\Produit;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Validation\ValidationException;

// class PanierService
// {
//     /**
//      * Récupérer le panier actif de l'utilisateur
//      */
//     public function index(int $utilisateurId)
//     {
//         return Panier::with(['produit', 'configuration'])
//             ->where('utilisateur_id', $utilisateurId)
//             ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif' au lieu de 'panier'
//             ->get()
//             ->map(function ($item) {
//                 return [
//                     'id' => $item->id,
//                     'produit_id' => $item->produit_id,
//                     'configuration_id' => $item->configuration_id,
//                     'titre' => $item->titre,
//                     'quantite' => $item->quantite,
//                     'prix_unitaire' => $item->prix_unitaire,
//                     'statut' => $item->statut,
//                     'produit' => $item->produit ? [
//                         'id' => $item->produit->id,
//                         'nom' => $item->produit->nom,
//                         'type_produit' => $item->produit->type_produit,
//                         'prix' => $item->produit->prix,
//                     ] : null,
//                     'configuration' => $item->configuration ? [
//                         'id' => $item->configuration->id,
//                         'nom_configuration' => $item->configuration->nom_configuration,
//                         'nom_configuration_autre' => $item->configuration->nom_configuration_autre,
//                         'prix_total' => $item->configuration->prix_total,
//                     ] : null,
//                 ];
//             });
//     }

//     /**
//      * Ajouter un article au panier
//      */
//     public function addItem(int $utilisateurId, array $data)
//     {
//         $produit = Produit::findOrFail($data['produit_id']);
//         $quantite = $data['quantite'] ?? 1;
//         $configId = $data['configuration_id'] ?? null;

//         // Vérifier si le produit avec la même configuration existe déjà dans le panier actif
//         $existingItem = Panier::where('utilisateur_id', $utilisateurId)
//             ->where('produit_id', $produit->id)
//             ->where('configuration_id', $configId)
//             ->where('statut', Panier::STATUT_ACTIF)
//             ->first();

//         if ($existingItem) {
//             // Mettre à jour la quantité
//             $existingItem->quantite += $quantite;
//             $existingItem->save();
            
//             return $this->index($utilisateurId);
//         }

//         // Créer un nouvel article
//         Panier::create([
//             'utilisateur_id' => $utilisateurId,
//             'statut' => Panier::STATUT_ACTIF,
//             'produit_id' => $produit->id,
//             'configuration_id' => $configId,
//             'titre' => $data['titre'] ?? $produit->nom,
//             'prix_unitaire' => $data['prix_unitaire'] ?? $produit->prix,
//             'quantite' => $quantite,
//         ]);

//         return $this->index($utilisateurId);
//     }

//     /**
//      * Modifier la quantité
//      */
//     public function updateQuantity(int $utilisateurId, int $itemId, int $quantite)
//     {
//         $panier = Panier::where('id', $itemId)
//             ->where('utilisateur_id', $utilisateurId)
//             ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif'
//             ->first();

//         if (!$panier) {
//             throw ValidationException::withMessages([
//                 'item' => ['Article non trouvé dans le panier actif']
//             ]);
//         }

//         $panier->quantite = $quantite;
//         $panier->save();

//         return $this->index($utilisateurId);
//     }

//     /**
//      * Supprimer un article du panier
//      */
//     public function removeItem(int $utilisateurId, int $itemId)
//     {
//         $panier = Panier::where('id', $itemId)
//             ->where('utilisateur_id', $utilisateurId)
//             ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif'
//             ->first();

//         if (!$panier) {
//             throw ValidationException::withMessages([
//                 'item' => ['Article non trouvé dans le panier actif']
//             ]);
//         }

//         $panier->delete();

//         return $this->index($utilisateurId);
//     }

//     /**
//      * Vider le panier actif
//      */
//     public function clear(int $utilisateurId)
//     {
//         Panier::where('utilisateur_id', $utilisateurId)
//             ->where('statut', Panier::STATUT_ACTIF)  // Changé: 'actif'
//             ->delete();

//         return $this->index($utilisateurId);
//     }
// }       

namespace App\Services\Paniers;

use App\Models\Panier;
use App\Models\Produit;
use App\Models\BoutiqueMisa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class PanierService
{
    const STATUT_ACTIF = 'actif';

    /**
     * Récupérer le panier actif de l'utilisateur
     */
    public function index(int $utilisateurId)
    {
        return Panier::with(['produit', 'configuration', 'boutique'])
            ->where('utilisateur_id', $utilisateurId)
            ->where('statut', self::STATUT_ACTIF)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'produit_id' => $item->produit_id,
                    'boutique_id' => $item->boutique_id,
                    'configuration_id' => $item->configuration_id,
                    'titre' => $item->titre,
                    'quantite' => $item->quantite,
                    'prix_unitaire' => $item->prix_unitaire,
                    'sous_total' => $item->quantite * $item->prix_unitaire,
                    'statut' => $item->statut,
                    'produit' => $item->produit ? [
                        'id' => $item->produit->id,
                        'nom' => $item->produit->nom,
                        'type_produit' => $item->produit->type_produit,
                        'prix' => $item->produit->prix,
                        'images' => $item->produit->images ?? [],
                    ] : null,
                    'boutique' => $item->boutique ? [
                        'id' => $item->boutique->id,
                        'nom' => $item->boutique->nom,
                        'prix' => $item->boutique->prix,
                        'image_url' => $item->boutique->image_url,
                        'stock' => $item->boutique->stock,
                    ] : null,
                    'configuration' => $item->configuration ? [
                        'id' => $item->configuration->id,
                        'nom_configuration' => $item->configuration->nom_configuration,
                        'nom_configuration_autre' => $item->configuration->nom_configuration_autre,
                        'prix_total' => $item->configuration->prix_total,
                    ] : null,
                ];
            });
    }


    /**
     * Ajouter un article au panier (gère produit_id ET boutique_id)
     */
    public function addItem(int $utilisateurId, array $data)
    {
        try {
            Log::info('PanierService - addItem', [
                'data' => $data,
                'user_id' => $utilisateurId
            ]);

            // ✅ Vérifier qu'au moins un des deux IDs est présent
            if (empty($data['produit_id']) && empty($data['boutique_id'])) {
                throw ValidationException::withMessages([
                    'produit_id' => ['Veuillez spécifier un produit ou un article de la boutique Misa.'],
                ]);
            }

            $produit = null;
            $boutique = null;
            $prixUnitaire = $data['prix_unitaire'] ?? 0;
            $titre = $data['titre'] ?? '';
            $quantite = $data['quantite'] ?? 1;

            // ✅ Si produit_id est fourni
            if (!empty($data['produit_id'])) {
                $produit = Produit::find($data['produit_id']);
                if (!$produit) {
                    throw ValidationException::withMessages([
                        'produit_id' => ['Produit non trouvé.'],
                    ]);
                }
                $prixUnitaire = $produit->prix ?? 0;
                $titre = $produit->nom ?? '';
                
                // ✅ Vérifier le stock pour produit classique
                if ($produit->quantite_stock < $quantite) {
                    throw ValidationException::withMessages([
                        'produit_id' => ['Stock insuffisant pour le produit. Stock disponible: ' . $produit->quantite_stock],
                    ]);
                }
            }

            // ✅ Si boutique_id est fourni
            if (!empty($data['boutique_id'])) {
                $boutique = BoutiqueMisa::find($data['boutique_id']);
                if (!$boutique) {
                    throw ValidationException::withMessages([
                        'boutique_id' => ['Article Misa non trouvé.'],
                    ]);
                }
                $prixUnitaire = $boutique->prix ?? 0;
                $titre = $boutique->nom ?? '';
                
                // ✅ Vérifier le stock pour boutique Misa
                if ($boutique->stock < $quantite) {
                    throw ValidationException::withMessages([
                        'boutique_id' => ['Stock insuffisant pour l\'article Misa. Stock disponible: ' . $boutique->stock],
                    ]);
                }
            }

            // ✅ Vérifier si l'article existe déjà dans le panier
            $existingItem = Panier::where('utilisateur_id', $utilisateurId)
                ->where('statut', self::STATUT_ACTIF)
                ->where(function($query) use ($data) {
                    if (!empty($data['produit_id'])) {
                        $query->where('produit_id', $data['produit_id']);
                    }
                    if (!empty($data['boutique_id'])) {
                        $query->where('boutique_id', $data['boutique_id']);
                    }
                })
                ->first();

            if ($existingItem) {
                // ✅ Mettre à jour la quantité
                $existingItem->quantite += $quantite;
                $existingItem->prix_unitaire = $prixUnitaire;
                $existingItem->titre = $titre;
                $existingItem->save();
                
                Log::info('PanierService - Article mis à jour', ['item_id' => $existingItem->id]);
                return $this->index($utilisateurId);
            }

            // ✅ Créer un nouvel article
            $newItem = Panier::create([
                'utilisateur_id' => $utilisateurId,
                'statut' => self::STATUT_ACTIF,
                'produit_id' => $data['produit_id'] ?? null,
                'boutique_id' => $data['boutique_id'] ?? null,
                'configuration_id' => $data['configuration_id'] ?? null,
                'titre' => $titre,
                'prix_unitaire' => $prixUnitaire,
                'quantite' => $quantite,
            ]);

            Log::info('PanierService - Nouvel article créé', ['item_id' => $newItem->id]);
            return $this->index($utilisateurId);

        } catch (\Exception $e) {
            Log::error('PanierService - addItem error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    
    /**
     * Modifier la quantité
     */
    public function updateQuantity(int $utilisateurId, int $itemId, int $quantite)
    {
        $panier = Panier::where('id', $itemId)
            ->where('utilisateur_id', $utilisateurId)
            ->where('statut', self::STATUT_ACTIF)
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
            ->where('statut', self::STATUT_ACTIF)
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
            ->where('statut', self::STATUT_ACTIF)
            ->delete();

        return $this->index($utilisateurId);
    }

    /**
     * Obtenir le panier complet avec les détails
     */
    public function getCartDetails(int $utilisateurId)
    {
        $items = $this->index($utilisateurId);
        $total = $items->sum('sous_total');
        $count = $items->sum('quantite');

        return [
            'items' => $items,
            'total' => $total,
            'count' => $count,
        ];
    }
}