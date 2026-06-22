<?php

namespace App\Http\Controllers\Api\Paniers;

use App\Http\Controllers\Controller;
use App\Services\Paniers\PanierService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Panier",
 *     description="Gestion du panier client"
 * )
 */
class PanierController extends Controller
{
    public function __construct(
        private readonly PanierService $panierService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/panier",
     *     summary="Lister le panier actif",
     *     tags={"Panier"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Panier récupéré"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $result = $this->panierService->index((int) $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Panier récupéré avec succès.',
            'data' => $result,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/panier/ajouter",
     *     summary="Ajouter un article au panier",
     *     tags={"Panier"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"produit_id"},
     *             @OA\Property(property="produit_id", type="integer", example=12),
     *             @OA\Property(property="quantite", type="integer", example=1, minimum=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Article ajouté"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    // public function ajouter(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'produit_id' => ['required', 'integer', 'exists:produits,id'],
    //             'quantite' => ['nullable', 'integer', 'min:1'],
    //             'configuration_id' => ['nullable', 'integer', 'exists:configurations,id'],
    //             'titre' => ['nullable', 'string', 'max:255'],
    //             'prix_unitaire' => ['nullable', 'numeric'],
    //         ]);

    //         $result = $this->panierService->addItem((int) $request->user()->id, $validated);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Article ajouté au panier avec succès.',
    //             'data' => $result,
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur de validation',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur serveur',
    //         ], 500);
    //     }
    // }

    public function ajouter(Request $request)
    {
        try {
            $validated = $request->validate([
                'produit_id' => ['nullable', 'integer', 'exists:produits,id'],
                'boutique_id' => ['nullable', 'integer', 'exists:boutique_misa,id'],
                'quantite' => ['nullable', 'integer', 'min:1'],
                'configuration_id' => ['nullable', 'integer', 'exists:configurations,id'],
                'titre' => ['nullable', 'string', 'max:255'],
                'prix_unitaire' => ['nullable', 'numeric'],
            ]);

            // ✅ Vérifier qu'au moins un des deux est présent
            if (empty($validated['produit_id']) && empty($validated['boutique_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez spécifier un produit ou un article de la boutique Misa.',
                    'errors' => [
                        'produit_id' => ['Au moins un produit ou article Misa est requis']
                    ]
                ], 422);
            }

            $result = $this->panierService->addItem((int) $request->user()->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Article ajouté au panier avec succès.',
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Erreur ajout panier', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/panier/modifier/{itemId}",
     *     summary="Modifier la quantité d'un article",
     *     tags={"Panier"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="itemId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantite"},
     *             @OA\Property(property="quantite", type="integer", example=2, minimum=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Quantité modifiée"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function modifierQuantite(Request $request, int $itemId)
    {
        try {
            $validated = $request->validate([
                'quantite' => ['required', 'integer', 'min:1'],
            ]);

            $result = $this->panierService->updateQuantity(
                (int) $request->user()->id,
                $itemId,
                (int) $validated['quantite']
            );

            return response()->json([
                'success' => true,
                'message' => 'Quantité modifiée avec succès.',
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/panier/supprimer/{itemId}",
     *     summary="Supprimer un article du panier",
     *     tags={"Panier"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="itemId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Article supprimé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function supprimer(Request $request, int $itemId)
    {
        try {
            $result = $this->panierService->removeItem((int) $request->user()->id, $itemId);

            return response()->json([
                'success' => true,
                'message' => 'Article supprimé du panier avec succès.',
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/panier/vider",
     *     summary="Vider le panier actif",
     *     tags={"Panier"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Panier vidé")
     * )
     */
    public function vider(Request $request)
    {
        $result = $this->panierService->clear((int) $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé avec succès.',
            'data' => $result,
        ], 200);
    }
}
