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
     * Uploader une image pour un produit (Admin)
     */
    public function store(Request $request, int $produitId)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:10240',
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
     * Uploader plusieurs images pour un produit (Admin)
     */
    public function storeMultiple(Request $request, int $produitId)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'alts' => 'nullable|array',
            'alts.*' => 'nullable|string|max:255'
        ]);

        try {
            $images = $this->imageService->uploadMultipleImages(
                $produitId,
                $request->file('images'),
                $request->input('alts', [])
            );
            return response()->json(['success' => true, 'data' => $images], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer une image (Admin)
     */
    public function destroy(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        try {
            $deleted = $this->imageService->deleteImage($id);
            return response()->json(['success' => $deleted]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Définir une image comme principale (Admin)
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

    /**
     * Récupérer l'image principale d'un produit (Public)
     */
    public function getMain(int $produitId)
    {
        try {
            $image = $this->imageService->getMainImage($produitId);
            return response()->json(['success' => true, 'data' => $image]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupérer toutes les images d'un produit (Public)
     */
    public function getProductImages(int $produitId)
    {
        try {
            $images = $this->imageService->getProductImages($produitId);
            return response()->json(['success' => true, 'data' => $images]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}