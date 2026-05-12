<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Produits\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(name="Catégories", description="Gestion des catégories du catalogue")
 */
class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    private function ensureAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !($user instanceof Admin)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }
        return null;
    }

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Liste des catégories racines",
     *     tags={"Catégories"},
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Succès")
     * )
     */
    public function index(Request $request)
    {
        $type = $request->query('type');
        if ($type) {
            $categories = $this->categoryService->getMenuByType($type);
        } else {
            $categories = $this->categoryService->getAllCategories();
        }
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{slug}",
     *     summary="Détails d'une catégorie par slug",
     *     tags={"Catégories"},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Succès"),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function show(string $slug)
    {
        $category = $this->categoryService->getCategoryBySlug($slug);
        
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Catégorie non trouvée'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
            'breadcrumbs' => $this->categoryService->getBreadcrumbs($category->id)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Créer une catégorie (Admin)",
     *     tags={"Catégories"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"nom", "type"})),
     *     @OA\Response(response=201, description="Créé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }
        try {
            $category = $this->categoryService->createCategory($request->all());
            return response()->json(['success' => true, 'data' => $category], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     summary="Mettre à jour une catégorie (Admin)",
     *     tags={"Catégories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Mis à jour")
     * )
     */
    public function update(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }
        try {
            $category = $this->categoryService->updateCategory($id, $request->all());
            return response()->json(['success' => true, 'data' => $category]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     summary="Supprimer une catégorie (Admin)",
     *     tags={"Catégories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Supprimé")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }
        $deleted = $this->categoryService->deleteCategory($id);
        return response()->json(['success' => $deleted]);
    }

    /**
     * @OA\Patch(
     *     path="/api/categories/reorder",
     *     summary="Réorganiser les catégories (Admin)",
     *     tags={"Catégories"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Réorganisé")
     * )
     */
    public function reorder(Request $request)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }
        $this->categoryService->updateOrder($request->input('orders', []));
        return response()->json(['success' => true]);
    }
}
