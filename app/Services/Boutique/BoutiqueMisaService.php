<?php
// app/Services/Boutique/BoutiqueMisaService.php

// namespace App\Services\Boutique;

// use App\Models\BoutiqueMisa;
// use Illuminate\Database\Eloquent\Collection;
// use Illuminate\Pagination\LengthAwarePaginator;
// use Illuminate\Support\Facades\File;
// use Illuminate\Support\Facades\Request;

// class BoutiqueMisaService
// {
//     protected $baseUrl;

//     public function __construct()
//     {
//         // Récupérer l'URL de base de l'application
//         $this->baseUrl = rtrim(config('app.url'), '/');
//     }

//     public function getAll(array $filters = []): LengthAwarePaginator
//     {
//         $query = BoutiqueMisa::query();

//         if (!empty($filters['search'])) {
//             $query->where('nom', 'like', '%' . $filters['search'] . '%');
//         }

//         if (isset($filters['stock_min'])) {
//             $query->where('stock', '>=', $filters['stock_min']);
//         }

//         if (isset($filters['prix_max'])) {
//             $query->where('prix', '<=', $filters['prix_max']);
//         }

//         $perPage = $filters['per_page'] ?? 15;
//         return $query->orderBy('id', 'desc')->paginate($perPage);
//     }

//     public function findById(int $id): ?BoutiqueMisa
//     {
//         return BoutiqueMisa::find($id);
//     }

//     /**
//      * Sauvegarde l'image et retourne l'URL complète
//      */
//     private function saveImageAndGetUrl($image): string
//     {
//         // Générer un nom unique
//         $extension = $image->getClientOriginalExtension();
//         $filename = time() . '_' . uniqid() . '.' . $extension;
        
//         // Définir le chemin complet
//         $destinationPath = public_path('misa-boutique');
        
//         // Créer le dossier s'il n'existe pas
//         if (!File::exists($destinationPath)) {
//             File::makeDirectory($destinationPath, 0755, true);
//         }
        
//         // Déplacer l'image
//         $image->move($destinationPath, $filename);
        
//         // Retourner l'URL complète
//         return $this->baseUrl . '/misa-boutique/' . $filename;
//     }

//     /**
//      * Supprime l'image du dossier
//      */
//     private function deleteImageByUrl(string $imageUrl): void
//     {
//         if ($imageUrl) {
//             // Extraire le chemin relatif depuis l'URL complète
//             $relativePath = str_replace($this->baseUrl, '', $imageUrl);
//             $fullPath = public_path($relativePath);
            
//             if (File::exists($fullPath)) {
//                 File::delete($fullPath);
//             }
//         }
//     }

//     public function create(array $data): BoutiqueMisa
//     {
//         if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
//             $data['image_url'] = $this->saveImageAndGetUrl($data['image']);
//             unset($data['image']); // Supprimer la clé image pour ne pas la sauvegarder
//         }

//         return BoutiqueMisa::create($data);
//     }

//     public function update(int $id, array $data): ?BoutiqueMisa
//     {
//         $boutique = $this->findById($id);
//         if (!$boutique) {
//             return null;
//         }

//         if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
//             // Supprimer l'ancienne image
//             if ($boutique->image_url) {
//                 $this->deleteImageByUrl($boutique->image_url);
//             }
//             $data['image_url'] = $this->saveImageAndGetUrl($data['image']);
//             unset($data['image']);
//         }

//         $boutique->update($data);
//         return $boutique;
//     }

//     public function delete(int $id): bool
//     {
//         $boutique = $this->findById($id);
//         if (!$boutique) {
//             return false;
//         }

//         // Supprimer l'image associée
//         if ($boutique->image_url) {
//             $this->deleteImageByUrl($boutique->image_url);
//         }

//         return $boutique->delete();
//     }

//     public function updateStock(int $id, int $stock): ?BoutiqueMisa
//     {
//         $boutique = $this->findById($id);
//         if (!$boutique) {
//             return null;
//         }

//         $boutique->update(['stock' => $stock]);
//         return $boutique;
//     }
// }


namespace App\Services\Boutique;

use App\Models\BoutiqueMisa;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class BoutiqueMisaService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('app.url'), '/');
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = BoutiqueMisa::query();

        if (!empty($filters['search'])) {
            $query->where('nom', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['stock_min'])) {
            $query->where('stock', '>=', $filters['stock_min']);
        }

        if (isset($filters['prix_max'])) {
            $query->where('prix', '<=', $filters['prix_max']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?BoutiqueMisa
    {
        return BoutiqueMisa::find($id);
    }

    private function saveImageAndGetUrl($image): string
    {
        $extension = $image->getClientOriginalExtension();
        $filename = time() . '_' . uniqid() . '.' . $extension;
        
        $destinationPath = public_path('misa-boutique');
        
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }
        
        $image->move($destinationPath, $filename);
        
        return $this->baseUrl . '/misa-boutique/' . $filename;
    }

    private function deleteImageByUrl(string $imageUrl): void
    {
        if ($imageUrl) {
            $relativePath = str_replace($this->baseUrl, '', $imageUrl);
            $fullPath = public_path($relativePath);
            
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
    }

    public function create(array $data): BoutiqueMisa
    {
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['image_url'] = $this->saveImageAndGetUrl($data['image']);
            unset($data['image']);
        }

        return BoutiqueMisa::create($data);
    }

    public function update(int $id, array $data): ?BoutiqueMisa
    {
        $boutique = $this->findById($id);
        if (!$boutique) {
            return null;
        }

        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            if ($boutique->image_url) {
                $this->deleteImageByUrl($boutique->image_url);
            }
            $data['image_url'] = $this->saveImageAndGetUrl($data['image']);
            unset($data['image']);
        }

        $boutique->update($data);
        return $boutique;
    }

    public function delete(int $id): bool
    {
        $boutique = $this->findById($id);
        if (!$boutique) {
            return false;
        }

        if ($boutique->image_url) {
            $this->deleteImageByUrl($boutique->image_url);
        }

        return $boutique->delete();
    }

    public function updateStock(int $id, int $stock): ?BoutiqueMisa
    {
        $boutique = $this->findById($id);
        if (!$boutique) {
            return null;
        }

        $boutique->update(['stock' => $stock]);
        return $boutique;
    }
}