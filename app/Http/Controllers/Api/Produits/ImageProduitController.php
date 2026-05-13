<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Produits\ImageProduitService;
use Illuminate\Http\Request;
use Throwable;

/**
 * @OA\Tag(name="Images Produits", description="Gestion des images de la galerie produit")
 */
class ImageProduitController extends Controller
{
    protected $imageService;

    public function __construct(ImageProduitService $imageService)
    {
        $this->imageService = $imageService;
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
     * @OA\Post(
     *     path="/api/produits/{produitId}/images",
     *     summary="Uploader une image pour un produit (Admin)",
     *     tags={"Images Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="produitId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="alt", type="string"),
     *                 @OA\Property(property="ordre", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Upload réussi")
     * )
     */
    public function store(Request $request, int $produitId)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'alt' => 'nullable|string|max:255',
            'ordre' => 'nullable|integer'
        ]);

        try {
            $image = $this->imageService->uploadImage(
                $produitId,
                $request->file('image'),
                $request->input('alt'),
                $request->input('ordre')
            );
            return response()->json(['success' => true, 'data' => $image], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/images/{id}",
     *     summary="Supprimer une image (Admin)",
     *     tags={"Images Produits"},
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
        $deleted = $this->imageService->deleteImage($id);
        return response()->json(['success' => $deleted]);
    }

    /**
     * @OA\Patch(
     *     path="/api/produits/{produitId}/images/{imageId}/set-main",
     *     summary="Définir une image comme principale (Admin)",
     *     tags={"Images Produits"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="produitId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="imageId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Succès")
     * )
     */
    public function setMain(Request $request, int $produitId, int $imageId)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }
        try {
            $this->imageService->setMainImage($produitId, $imageId);
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
