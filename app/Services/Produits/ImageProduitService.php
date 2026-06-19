<?php

namespace App\Services\Produits;

use App\Repositories\ImageProduit\ImageProduitRepositoryInterface;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImageProduitService
{
    protected $imageRepository;
    protected $storagePath;
    protected $storageUrlBase;
    protected const MAX_SIZE_WITHOUT_COMPRESSION = 1048576; // 1 Mo

    public function __construct(ImageProduitRepositoryInterface $imageRepository)
    {
        $this->imageRepository = $imageRepository;

        // Utiliser le stockage public de Laravel
        $this->storagePath = storage_path('app/public/produits');
        $this->storageUrlBase = '/storage/produits';

        // Création automatique du dossier s'il n'existe pas
        if (!File::exists($this->storagePath)) {
            File::makeDirectory($this->storagePath, 0755, true);
            Log::info('📁 Dossier de stockage créé', ['path' => $this->storagePath]);
        }
    }

    /**
     * Upload et stockage d'une image produit
     */
    public function uploadImage(int $produitId, UploadedFile $file, string $alt = null, int $ordre = null)
    {
        Log::info('🚀 Début upload image', [
            'produit_id' => $produitId,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_extension' => $file->getClientOriginalExtension()
        ]);

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception("Format de fichier non autorisé. Utilisez jpg, jpeg, png, webp ou gif.");
        }

        // Vérifier la taille
        if ($file->getSize() > 10485760) { // 10MB
            throw new Exception("Le fichier est trop volumineux. Maximum 10MB.");
        }

        // Générer un nom unique
        $filename = time() . '_' . Str::uuid() . '.' . $extension;
        $targetPath = $this->storagePath . DIRECTORY_SEPARATOR . $filename;

        Log::info('📝 Sauvegarde du fichier', [
            'target_path' => $targetPath,
            'storage_path' => $this->storagePath
        ]);

        try {
            // S'assurer que le dossier existe
            if (!File::exists($this->storagePath)) {
                File::makeDirectory($this->storagePath, 0755, true);
            }

            // Compression automatique pour les fichiers > 1 Mo.
            if ($file->getSize() > self::MAX_SIZE_WITHOUT_COMPRESSION) {
                Log::info('🔄 Compression de l\'image...');
                $this->compressAndSaveImage($file, $targetPath, $extension);
            } else {
                $file->move($this->storagePath, $filename);
            }

            // S'assurer que le fichier a bien été créé
            if (!File::exists($targetPath)) {
                throw new Exception("Le fichier n'a pas pu être sauvegardé.");
            }

            Log::info('✅ Fichier sauvegardé', [
                'path' => $targetPath,
                'size' => File::size($targetPath)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la sauvegarde', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Erreur lors de la sauvegarde de l\'image: ' . $e->getMessage());
        }

        // Construire l'URL
        $url = $this->storageUrlBase . '/' . $filename;

        // Déterminer l'ordre
        $existingImages = $this->imageRepository->findByProduit($produitId);
        if ($ordre === null) {
            $ordre = ($existingImages->count() === 0) ? 0 : ((int) $existingImages->max('ordre') + 1);
        }

        // Créer l'entrée en base de données
        $imageData = [
            'produit_id' => $produitId,
            'url' => $url,
            'alt' => $alt ?: "Image produit {$produitId}",
            'ordre' => $ordre,
        ];

        Log::info('💾 Création entrée base de données', $imageData);

        $image = $this->imageRepository->create($imageData);

        // Mettre à jour l'image principale du produit si c'est la première
        if ($ordre === 0 || $existingImages->count() === 0) {
            $this->updateProductMainImage($produitId, $url);
        }

        Log::info('🎉 Upload terminé avec succès', ['image_id' => $image->id]);

        return $image;
    }

    /**
     * Mettre à jour l'image principale du produit
     */
    protected function updateProductMainImage(int $produitId, string $url)
    {
        try {
            $produit = \App\Models\Produit::find($produitId);
            if ($produit) {
                $produit->image_principale = $url;
                $produit->save();
                Log::info('✅ Image principale mise à jour', [
                    'produit_id' => $produitId,
                    'url' => $url
                ]);
            } else {
                Log::warning('⚠️ Produit non trouvé pour la mise à jour de l\'image principale', ['produit_id' => $produitId]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la mise à jour de l\'image principale', [
                'produit_id' => $produitId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Upload d'images multiples
     */
    public function uploadMultipleImages(int $produitId, array $files, array $alts = [])
    {
        $uploaded = [];
        $ordre = $this->imageRepository->findByProduit($produitId)->count();

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $alt = isset($alts[$index]) ? $alts[$index] : null;
                    $image = $this->uploadImage($produitId, $file, $alt, $ordre + $index);
                    $uploaded[] = $image;
                } catch (Exception $e) {
                    Log::error("❌ Erreur upload image " . ($index + 1) . ": " . $e->getMessage());
                    continue;
                }
            }
        }

        return $uploaded;
    }

    /**
     * Définir une image comme principale (ordre = 0)
     */
    public function setMainImage(int $produitId, int $imageId)
    {
        try {
            $images = $this->imageRepository->findByProduit($produitId);
            $mainImageUrl = null;

            foreach ($images as $img) {
                if ($img->id === $imageId) {
                    $this->imageRepository->update($img->id, ['ordre' => 0]);
                    $mainImageUrl = $img->url;
                    Log::info('🔄 Image définie comme principale', ['image_id' => $imageId]);
                } elseif ($img->ordre === 0) {
                    $this->imageRepository->update($img->id, ['ordre' => 1]);
                }
            }

            // Mettre à jour l'image principale du produit
            if ($mainImageUrl) {
                $this->updateProductMainImage($produitId, $mainImageUrl);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la définition de l\'image principale', [
                'produit_id' => $produitId,
                'image_id' => $imageId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Suppression d'une image (base de données + fichier)
     */
    public function deleteImage(int $imageId)
    {
        try {
            $image = $this->imageRepository->findById($imageId);

            if (!$image) {
                Log::warning('⚠️ Image non trouvée pour suppression', ['image_id' => $imageId]);
                return false;
            }

            // Supprimer le fichier physique
            $this->deleteImageFile($image->url);

            // Supprimer l'entrée en base
            $result = $this->imageRepository->delete($imageId);

            Log::info('🗑️ Image supprimée', ['image_id' => $imageId]);

            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la suppression de l\'image', [
                'image_id' => $imageId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Suppression de toutes les images d'un produit
     */
    public function deleteAllProductImages(int $produitId)
    {
        try {
            $images = $this->imageRepository->findByProduit($produitId);

            foreach ($images as $image) {
                $this->deleteImageFile($image->url);
                $this->imageRepository->delete($image->id);
            }

            // Supprimer l'image principale du produit
            $produit = \App\Models\Produit::find($produitId);
            if ($produit) {
                $produit->image_principale = null;
                $produit->save();
            }

            Log::info('🗑️ Toutes les images du produit supprimées', ['produit_id' => $produitId]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la suppression des images du produit', [
                'produit_id' => $produitId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Helper pour supprimer le fichier physique
     */
    protected function deleteImageFile(string $url)
    {
        try {
            // Extraire le nom du fichier de l'URL
            $filename = basename((string) parse_url($url, PHP_URL_PATH));
            $filePath = $this->storagePath . DIRECTORY_SEPARATOR . $filename;

            if (File::exists($filePath)) {
                File::delete($filePath);
                Log::info('📁 Fichier image supprimé', ['path' => $filePath]);
                return true;
            }
            
            Log::warning('⚠️ Fichier image non trouvé', ['path' => $filePath]);
            return false;

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la suppression du fichier image', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mise à jour du texte ALT
     */
    public function updateAlt(int $imageId, string $alt)
    {
        try {
            $result = $this->imageRepository->update($imageId, ['alt' => $alt]);
            Log::info('✏️ Texte ALT mis à jour', ['image_id' => $imageId, 'alt' => $alt]);
            return $result;
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la mise à jour du texte ALT', [
                'image_id' => $imageId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Récupérer l'image principale d'un produit
     */
    public function getMainImage(int $produitId)
    {
        try {
            // Vérifier d'abord dans le produit
            $produit = \App\Models\Produit::find($produitId);
            if ($produit && $produit->image_principale) {
                // Chercher l'image correspondante
                $images = $this->imageRepository->findByProduit($produitId);
                foreach ($images as $image) {
                    if ($image->url === $produit->image_principale) {
                        return $image;
                    }
                }
            }

            // Sinon, prendre la première image (ordre 0)
            return $this->imageRepository->findMainImage($produitId);
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la récupération de l\'image principale', [
                'produit_id' => $produitId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Récupérer toutes les images d'un produit
     */
    public function getProductImages(int $produitId)
    {
        try {
            return $this->imageRepository->findByProduit($produitId);
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la récupération des images du produit', [
                'produit_id' => $produitId,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Compression et sauvegarde d'une image
     */
    protected function compressAndSaveImage(UploadedFile $file, string $targetPath, string $extension): void
    {
        $sourcePath = $file->getRealPath();

        try {
            // Vérifier que la source existe
            if (!File::exists($sourcePath)) {
                throw new Exception("Le fichier source n'existe pas.");
            }

            // Vérifier que le dossier de destination existe
            $destinationDir = dirname($targetPath);
            if (!File::exists($destinationDir)) {
                File::makeDirectory($destinationDir, 0755, true);
            }

            // Créer une image GD
            $image = null;
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    if (function_exists('imagecreatefromjpeg')) {
                        $image = imagecreatefromjpeg($sourcePath);
                        if ($image) {
                            imagejpeg($image, $targetPath, 75);
                            imagedestroy($image);
                            Log::info('✅ Image JPEG compressée');
                            return;
                        }
                    }
                    break;

                case 'png':
                    if (function_exists('imagecreatefrompng')) {
                        $image = imagecreatefrompng($sourcePath);
                        if ($image) {
                            // Préserver la transparence
                            imagepalettetotruecolor($image);
                            imagealphablending($image, true);
                            imagesavealpha($image, true);
                            imagepng($image, $targetPath, 7);
                            imagedestroy($image);
                            Log::info('✅ Image PNG compressée');
                            return;
                        }
                    }
                    break;

                case 'webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $image = imagecreatefromwebp($sourcePath);
                        if ($image) {
                            imagewebp($image, $targetPath, 75);
                            imagedestroy($image);
                            Log::info('✅ Image WEBP compressée');
                            return;
                        }
                    }
                    break;

                case 'gif':
                    if (function_exists('imagecreatefromgif')) {
                        $image = imagecreatefromgif($sourcePath);
                        if ($image) {
                            imagegif($image, $targetPath);
                            imagedestroy($image);
                            Log::info('✅ Image GIF compressée');
                            return;
                        }
                    }
                    break;
            }

            throw new Exception("Impossible de compresser l'image.");

        } catch (\Throwable $e) {
            Log::warning('⚠️ Compression échouée, fallback sur la copie directe: ' . $e->getMessage());
            // Fallback: copie directe
            $file->move($this->storagePath, basename($targetPath));
            Log::info('✅ Image sauvegardée sans compression');
        }
    }

    /**
     * Vérifier si une image existe
     */
    public function imageExists(int $imageId): bool
    {
        try {
            $image = $this->imageRepository->findById($imageId);
            if (!$image) {
                return false;
            }
            
            $filename = basename((string) parse_url($image->url, PHP_URL_PATH));
            $filePath = $this->storagePath . DIRECTORY_SEPARATOR . $filename;
            
            return File::exists($filePath);
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la vérification de l\'image', [
                'image_id' => $imageId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Récupérer le nombre d'images d'un produit
     */
    public function countProductImages(int $produitId): int
    {
        try {
            return $this->imageRepository->findByProduit($produitId)->count();
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors du comptage des images', [
                'produit_id' => $produitId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}