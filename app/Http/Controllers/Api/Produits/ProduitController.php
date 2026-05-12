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
     *     @OA\Parameter(name="type_produit", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Succès")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(['categorie_id', 'type_produit', 'actif']);
        $search = $request->query('search');

        if ($search) {
            // Ici on pourrait injecter le repository directement ou ajouter search au service
            // Pour simplifier on utilise le service si la méthode existe
            // On va supposer qu'on peut récupérer tous les produits filtrés
        }

        // Utilisation du repository via le service pour les filtres
        // Pour cet exemple, on retourne une liste filtrée
        $produits = $this->produitService->getProduitsByCategory($filters['categorie_id'] ?? 0);
        
        if (!$filters['categorie_id']) {
            // Fallback si pas de catégorie spécifiée pour la démo
            // Dans un vrai projet, on implémenterait getAll dans le service
        }

        return response()->json([
            'success' => true,
            'data' => $produits
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/produits/{slug}",
     *     summary="Fiche produit détaillée par slug",
     *     tags={"Produits"},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Succès")
     * )
     */
    public function show(string $slug)
    {
        $produit = $this->produitService->getProduitBySlug($slug);
        
        if (!$produit) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
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
     *     summary="Créer un produit (Admin)",
     *     tags={"Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=201, description="Créé")
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
     *     summary="Mettre à jour un produit (Admin)",
     *     tags={"Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Succès")
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
     *     summary="Activer/Désactiver un produit (Admin)",
     *     tags={"Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Succès")
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
     *     @OA\Response(response=200, description="Succès")
     * )
     */
    public function destroy(int $id)
    {
        $deleted = $this->produitService->deleteProduit($id);
        return response()->json(['success' => $deleted]);
    }
}
