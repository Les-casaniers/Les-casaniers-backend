<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Services\Produits\ProduitService;
use App\Services\Produits\AttributProduitService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(name="Produits", description="Gestion des produits du catalogue")
 */
class ProduitController extends Controller
{
    protected $produitService;
    protected $attributService;

    public function __construct(ProduitService $produitService, AttributProduitService $attributService)
    {
        $this->produitService = $produitService;
        $this->attributService = $attributService;
    }

    /**
     * @OA\Get(
     *     path="/api/produits",
     *     summary="Liste des produits avec filtres",
     *     tags={"Produits"},
     *     @OA\Parameter(name="categorie_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id_sous_categorie", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="SuccÃ¨s")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(['categorie_id', 'id_sous_categorie', 'actif', 'est_dispo']);
        $search = $request->query('search');

        $produits = $this->produitService->getProduits($filters, $search);

        return response()->json([
            'success' => true,
            'data' => $produits
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/produits/{id}",
     *     summary="Fiche produit dÃ©taillÃ©e par id",
     *     tags={"Produits"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="SuccÃ¨s")
     * )
     */
    public function show(int $id)
    {
        $produit = $this->produitService->getProduitById($id);
        
        if (!$produit) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvÃ©'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $produit,
            'technical_sheet' => $this->attributService->getTechnicalSheet($produit->id)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/produits",
     *     summary="CrÃ©er un produit (Admin)",
     *     tags={"Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=201, description="CrÃ©Ã©")
     * )
     */
    public function store(Request $request)
    {
        try {
            $produit = $this->produitService->createProduit($request->all());
            return response()->json(['success' => true, 'data' => $produit], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/produits/{id}",
     *     summary="Mettre Ã  jour un produit (Admin)",
     *     tags={"Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="SuccÃ¨s")
     * )
     */
    public function update(Request $request, int $id)
    {
        try {
            $produit = $this->produitService->updateProduit($id, $request->all());
            return response()->json(['success' => true, 'data' => $produit]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/produits/{id}/toggle-status",
     *     summary="Activer/DÃ©sactiver un produit (Admin)",
     *     tags={"Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="SuccÃ¨s")
     * )
     */
    public function toggleStatus(Request $request, int $id)
    {
        $produit = $this->produitService->toggleStatus($id, $request->boolean('actif'));
        return response()->json(['success' => true, 'data' => $produit]);
    }

    /**
     * @OA\Delete(
     *     path="/api/produits/{id}",
     *     summary="Supprimer un produit (Admin)",
     *     tags={"Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="SuccÃ¨s")
     * )
     */
    public function destroy(int $id)
    {
        $deleted = $this->produitService->deleteProduit($id);
        return response()->json(['success' => $deleted]);
    }
}

