<?php

namespace App\Http\Controllers\Api\Panier;

use App\Http\Controllers\Controller;
use App\Models\Panier;
use App\Models\ItemPanier;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Panier",
 *     description="Gestion du panier d'achat"
 * )
 */
class PanierController extends Controller
{
    /**
     * Récupérer ou créer le panier actif de l'utilisateur
     */
    private function getActiveCart($userId)
    {
        $cart = Panier::where('utilisateur_id', $userId)
            ->where('statut', 'actif')
            ->with('items.produit')
            ->first();
        
        if (!$cart) {
            $cart = Panier::create([
                'utilisateur_id' => $userId,
                'statut' => 'actif',
                'date_creation' => now(),
                'date_modification' => now()
            ]);
        }
        
        return $cart;
    }
    
    /**
     * @OA\Get(
     *     path="/api/panier",
     *     summary="Voir mon panier",
     *     tags={"Panier"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Détails du panier")
     * )
     */
    public function index(Request $request)
    {
        try {
            $panier = $this->getActiveCart($request->user()->id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'panier' => $panier,
                    'total' => $panier->total,
                    'nb_articles' => $panier->nb_articles,
                    'items' => $panier->items
                ],
                'message' => 'Panier récupéré avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/panier/ajouter",
     *     summary="Ajouter un produit au panier",
     *     tags={"Panier"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"produit_id", "quantite"},
     *             @OA\Property(property="produit_id", type="integer", example=1),
     *             @OA\Property(property="quantite", type="integer", example=1),
     *             @OA\Property(property="configuration_id", type="integer", example=null)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Produit ajouté")
     * )
     */
    public function ajouter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1',
            'configuration_id' => 'nullable|integer'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $produit = Produit::findOrFail($request->produit_id);
            $panier = $this->getActiveCart($request->user()->id);
            
            // Vérifier si le produit est déjà dans le panier
            $existingItem = ItemPanier::where('panier_id', $panier->id)
                ->where('produit_id', $request->produit_id)
                ->first();
            
            $prix = $produit->prix_promo ?? $produit->prix;
            $titre = $produit->nom;
            
            if ($existingItem) {
                // Mettre à jour la quantité
                $existingItem->quantite += $request->quantite;
                $existingItem->date_modification = now();
                $existingItem->save();
            } else {
                // Créer un nouvel item
                ItemPanier::create([
                    'panier_id' => $panier->id,
                    'produit_id' => $request->produit_id,
                    'configuration_id' => $request->configuration_id,
                    'titre' => $titre,
                    'prix_unitaire' => $prix,
                    'quantite' => $request->quantite,
                    'date_creation' => now(),
                    'date_modification' => now()
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $panier->load('items.produit'),
                'message' => 'Produit ajouté au panier'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/panier/modifier/{itemId}",
     *     summary="Modifier la quantité d'un item",
     *     tags={"Panier"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantite"},
     *             @OA\Property(property="quantite", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Quantité modifiée")
     * )
     */
    public function modifierQuantite(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'quantite' => 'required|integer|min:0'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $item = ItemPanier::whereHas('panier', function($q) use ($request) {
                $q->where('utilisateur_id', $request->user()->id)
                  ->where('statut', 'actif');
            })->findOrFail($itemId);
            
            if ($request->quantite <= 0) {
                $item->delete();
                $message = 'Produit retiré du panier';
            } else {
                $item->quantite = $request->quantite;
                $item->date_modification = now();
                $item->save();
                $message = 'Quantité mise à jour';
            }
            
            $panier = $this->getActiveCart($request->user()->id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'panier' => $panier,
                    'total' => $panier->total,
                    'nb_articles' => $panier->nb_articles
                ],
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item non trouvé'
            ], 404);
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/panier/supprimer/{itemId}",
     *     summary="Supprimer un item du panier",
     *     tags={"Panier"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Item supprimé")
     * )
     */
    public function supprimer(Request $request, $itemId)
    {
        try {
            $item = ItemPanier::whereHas('panier', function($q) use ($request) {
                $q->where('utilisateur_id', $request->user()->id)
                  ->where('statut', 'actif');
            })->findOrFail($itemId);
            
            $item->delete();
            
            $panier = $this->getActiveCart($request->user()->id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'panier' => $panier,
                    'total' => $panier->total,
                    'nb_articles' => $panier->nb_articles
                ],
                'message' => 'Produit supprimé du panier'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item non trouvé'
            ], 404);
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/panier/vider",
     *     summary="Vider tout le panier",
     *     tags={"Panier"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Panier vidé")
     * )
     */
    public function vider(Request $request)
    {
        try {
            $panier = $this->getActiveCart($request->user()->id);
            $panier->items()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Panier vidé avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
