<?php

namespace App\Services\Produits;

use App\Repositories\ImageProduit\ImageProduitRepositoryInterface;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageProduitService
{
    protected $imageRepository;
    protected $backendImagePath;
    protected $backendImageUrlBase;
    protected const MAX_SIZE_WITHOUT_COMPRESSION = 1048576; // 1 Mo

    public function __construct(ImageProduitRepositoryInterface $imageRepository)
    {
        $this->imageRepository = $imageRepository;

        $this->backendImagePath = base_path('image');
        $this->backendImageUrlBase = rtrim(config('app.url'), '/') . '/image';

        // Creation automatique du dossier backend s'il n'existe pas.
        if (!File::exists($this->backendImagePath)) {
            File::makeDirectory($this->backendImagePath, 0755, true);
        }
    }

    /**
     * Upload et stockage d'une image produit
     */
    public function uploadImage(int $produitId, UploadedFile $file, string $alt = null, int $ordre = null)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception("Format de fichier non autorise. Utilisez jpg, jpeg, png ou webp.");
        }

        $filename = Str::uuid() . '.' . $extension;
        $backendTargetPath = $this->backendImagePath . DIRECTORY_SEPARATOR . $filename;

        // Compression automatique pour les fichiers > 1 Mo.
        if ($file->getSize() > self::MAX_SIZE_WITHOUT_COMPRESSION) {
            $this->compressAndSaveImage($file, $backendTargetPath, $extension);
        } else {
            $file->move($this->backendImagePath, $filename);
        }

        $url = $this->backendImageUrlBase . '/' . $filename;

        if ($ordre === null) {
            $existingImages = $this->imageRepository->findByProduit($produitId);
            $ordre = ($existingImages->count() === 0) ? 0 : ((int) $existingImages->max('ordre') + 1);
        }

        return $this->imageRepository->create([
            'produit_id' => $produitId,
            'url' => $url,
            'alt' => $alt ?: "Image produit {$produitId}",
            'ordre' => $ordre,
        ]);
    }

    /**
     * Definir une image comme principale (ordre = 0)
     */
    public function setMainImage(int $produitId, int $imageId)
    {
        $images = $this->imageRepository->findByProduit($produitId);

        foreach ($images as $img) {
            if ($img->id === $imageId) {
                $this->imageRepository->update($img->id, ['ordre' => 0]);
            } elseif ($img->ordre === 0) {
                $this->imageRepository->update($img->id, ['ordre' => 1]);
            }
        }

        return true;
    }

    /**
     * Suppression d'une image (base de donnees + fichier)
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
     * Suppression de toutes les images d'un produit
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
        $filename = basename((string) parse_url($url, PHP_URL_PATH));
        $backendPath = $this->backendImagePath . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($backendPath)) {
            File::delete($backendPath);
        }
    }

    /**
     * Mise a jour du texte ALT
     */
    public function updateAlt(int $imageId, string $alt)
    {
        return $this->imageRepository->update($imageId, ['alt' => $alt]);
    }

    protected function compressAndSaveImage(UploadedFile $file, string $targetPath, string $extension): void
    {
        $sourcePath = $file->getRealPath();

        try {
            if (in_array($extension, ['jpg', 'jpeg'], true) && function_exists('imagecreatefromjpeg')) {
                $image = imagecreatefromjpeg($sourcePath);
                imagejpeg($image, $targetPath, 75);
                imagedestroy($image);
                return;
            }

            if ($extension === 'png' && function_exists('imagecreatefrompng')) {
                $image = imagecreatefrompng($sourcePath);
                imagepng($image, $targetPath, 7);
                imagedestroy($image);
                return;
            }

            if ($extension === 'webp' && function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($sourcePath);
                imagewebp($image, $targetPath, 75);
                imagedestroy($image);
                return;
            }
        } catch (\Throwable $e) {
            Log::warning('Image compression failed, fallback to original upload: ' . $e->getMessage());
        }

        $file->move($this->backendImagePath, basename($targetPath));
    }

}
