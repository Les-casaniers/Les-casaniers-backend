<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Services\Produits\AttributProduitService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(name="Attributs Produits", description="Gestion des caractéristiques techniques des produits")
 */
class AttributProduitController extends Controller
{
    protected $attributService;

    public function __construct(AttributProduitService $attributService)
    {
        $this->attributService = $attributService;
    }

    /**
     * @OA\Post(
     *     path="/api/produits/{produitId}/attributes/sync",
     *     summary="Synchroniser les attributs d'un produit (Admin)",
     *     description="Remplace tous les attributs existants par la nouvelle liste fournie.",
     *     tags={"Attributs Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="produitId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="cle_attr", type="string", example="socket"),
     *                 @OA\Property(property="valeur_attr", type="string", example="LGA1700"),
     *                 @OA\Property(property="libelle_attr", type="string", example="Socket CPU")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Succès")
     * )
     */
    public function sync(Request $request, int $produitId)
    {
        try {
            $attributes = $this->attributService->syncAttributes($produitId, $request->all());
            return response()->json(['success' => true, 'data' => $attributes]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/attributes/standard-keys",
     *     summary="Récupérer le dictionnaire des clés standard (Admin)",
     *     tags={"Attributs Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Succès")
     * )
     */
    public function getStandardKeys()
    {
        return response()->json([
            'success' => true,
            'data' => $this->attributService->getStandardKeys()
        ]);
    }
}
