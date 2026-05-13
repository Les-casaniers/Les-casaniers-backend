<?php

namespace App\Http\Controllers\Api\Favoris;

use App\Http\Controllers\Controller;
use App\Services\Favoris\FavoriService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Favoris",
 *     description="Gestion des produits favoris des utilisateurs"
 * )
 */
class FavorisController extends Controller
{
    public function __construct(
        private readonly FavoriService $favoriService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/favoris",
     *     summary="Lister mes favoris",
     *     tags={"Favoris"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Liste des favoris récupérée"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $favoris = $this->favoriService->listByUser((int) $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Liste des favoris récupérée avec succès.',
            'data' => $favoris,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/favoris",
     *     summary="Ajouter un produit aux favoris",
     *     tags={"Favoris"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"produit_id"},
     *             @OA\Property(property="produit_id", type="integer", example=12)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Favori ajouté"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'produit_id' => ['required', 'integer', 'exists:produits,id'],
            ]);

            $favori = $this->favoriService->addFavori(
                (int) $request->user()->id,
                (int) $validated['produit_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté aux favoris avec succès.',
                'data' => $favori,
            ], 201);
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
     *     path="/favoris/{produitId}",
     *     summary="Supprimer un produit des favoris",
     *     tags={"Favoris"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="produitId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Favori supprimé"),
     *     @OA\Response(response=404, description="Favori non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function destroy(Request $request, int $produitId)
    {
        try {
            $deleted = $this->favoriService->removeFavori(
                (int) $request->user()->id,
                $produitId
            );

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce produit n\'est pas présent dans vos favoris.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produit retiré des favoris avec succès.',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }
}
