<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Produits\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(name="CatÃ©gories", description="Gestion des catÃ©gories du catalogue")
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
            return response()->json(['success' => false, 'message' => 'AccÃ¨s refusÃ©'], 403);
        }
        return null;
    }

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Liste des catÃ©gories",
     *     tags={"CatÃ©gories"},
     *     @OA\Response(response=200, description="SuccÃ¨s")
     * )
     */
    public function index(Request $request)
    {
        $categories = $this->categoryService->getAllCategories();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     summary="DÃ©tails d'une catÃ©gorie par id",
     *     tags={"CatÃ©gories"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="SuccÃ¨s"),
     *     @OA\Response(response=404, description="Non trouvÃ©")
     * )
     */
    public function show(int $id)
    {
        $category = $this->categoryService->getCategoryById($id);
        
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'CatÃ©gorie non trouvÃ©e'], 404);
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
     *     summary="CrÃ©er une catÃ©gorie (Admin)",
     *     tags={"CatÃ©gories"},
     *     security={{"sanctum": {}}},
    *     @OA\RequestBody(required=true, @OA\JsonContent(required={"nom"})),
     *     @OA\Response(response=201, description="CrÃ©Ã©"),
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
     *     summary="Mettre Ã  jour une catÃ©gorie (Admin)",
     *     tags={"CatÃ©gories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Mis Ã  jour")
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
     *     summary="Supprimer une catÃ©gorie (Admin)",
     *     tags={"CatÃ©gories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="SupprimÃ©")
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

}


