<?php

namespace App\Services\Produits;

use App\Repositories\ImageProduit\ImageProduitRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Exception;
use Illuminate\Support\Facades\File;

class ImageProduitService
{
    protected $imageRepository;
    protected $disk = 'public';
    protected $folder = 'products';

    public function __construct(ImageProduitRepositoryInterface $imageRepository)
    {
        $this->imageRepository = $imageRepository;
        
        // Création du dossier s'il n'existe pas
        if (!Storage::disk($this->disk)->exists($this->folder)) {
            Storage::disk($this->disk)->makeDirectory($this->folder);
        }
    }

    /**
     * Upload et stockage d'une image produit
     */
    public function uploadImage(int $produitId, UploadedFile $file, string $alt = null, int $ordre = null)
    {
        // Validation basique (devrait être faite dans le contrôleur aussi)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new Exception("Format de fichier non autorisé. Utilisez jpg, jpeg, png ou webp.");
        }

        // Renommage sécurisé
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Stockage
        $path = $file->storeAs($this->folder, $filename, $this->disk);
        $fullPath = Storage::disk($this->disk)->path($path);

        // Optimisation de l'image
        try {
            ImageOptimizer::optimize($fullPath);
        } catch (\Exception $e) {
            \Log::warning("Image optimization failed: " . $e->getMessage());
        }

        // Génération de l'URL
        $url = Storage::disk($this->disk)->url($path);

        // Définir l'ordre si non spécifié (0 par défaut si première image)
        if ($ordre === null) {
            $existingImages = $this->imageRepository->findByProduit($produitId);
            $ordre = ($existingImages->count() === 0) ? 0 : ($existingImages->max('ordre') + 1);
        }

        // Création en base de données
        return $this->imageRepository->create([
            'produit_id' => $produitId,
            'url' => $url,
            'alt' => $alt ?: "Image produit {$produitId}",
            'ordre' => $ordre
        ]);
    }

    /**
     * Définir une image comme principale (ordre = 0)
     */
    public function setMainImage(int $produitId, int $imageId)
    {
        $images = $this->imageRepository->findByProduit($produitId);
        
        foreach ($images as $img) {
            if ($img->id === $imageId) {
                $this->imageRepository->update($img->id, ['ordre' => 0]);
            } else if ($img->ordre === 0) {
                // L'ancienne image principale devient secondaire
                $this->imageRepository->update($img->id, ['ordre' => 1]);
            }
        }

        return true;
    }

    /**
     * Suppression d'une image (base de données + fichier)
     */
    public function deleteImage(int $imageId)
    {
        $image = $this->imageRepository->findById($imageId);
        
        if ($image) {
            $this->deleteImageFile($image->url);
            return $this->imageRepository->delete($imageId);
        }

        return false;
    }

    /**
     * Suppression de toutes les images d'un produit (ex: lors de la suppression du produit)
     */
    public function deleteAllProductImages(int $produitId)
    {
        $images = $this->imageRepository->findByProduit($produitId);
        
        foreach ($images as $image) {
            $this->deleteImage($image->id);
        }

        return true;
    }

    /**
     * Helper pour supprimer le fichier physique
     */
    protected function deleteImageFile(string $url)
    {
        // On extrait le nom du fichier depuis l'URL
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $path = $this->folder . '/' . $filename;
        
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    /**
     * Mise à jour du texte ALT
     */
    public function updateAlt(int $imageId, string $alt)
    {
        return $this->imageRepository->update($imageId, ['alt' => $alt]);
    }
}
