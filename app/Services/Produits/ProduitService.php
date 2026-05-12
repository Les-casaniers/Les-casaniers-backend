<?php

namespace App\Services\Produits;

use App\Repositories\Produit\ProduitRepositoryInterface;
use App\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProduitService
{
    protected $produitRepository;
    protected $categoryRepository;

    public function __construct(
        ProduitRepositoryInterface $produitRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->produitRepository = $produitRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * CRUD Back-Office: Créer un produit
     */
    public function createProduit(array $data)
    {
        $this->validateProduit($data);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['nom']);
        }

        // Vérifier si la catégorie existe
        $category = $this->categoryRepository->findById($data['categorie_id']);
        if (!$category) {
            throw new Exception("La catégorie spécifiée n'existe pas.");
        }

        return $this->produitRepository->create($data);
    }

    /**
     * CRUD Back-Office: Mettre à jour un produit
     */
    public function updateProduit(int $id, array $data)
    {
        // On ne valide que les champs fournis pour l'édition partielle
        $validator = Validator::make($data, [
            'prix' => 'sometimes|numeric|min:0',
            'quantite_stock' => 'sometimes|integer|min:0',
            'description' => 'sometimes|nullable|string',
            'nom' => 'sometimes|string|max:255',
            'categorie_id' => 'sometimes|exists:categories,id',
            'actif' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (isset($data['nom']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['nom']);
        }

        return $this->produitRepository->update($id, $data);
    }

    /**
     * Activation / Désactivation
     */
    public function toggleStatus(int $id, bool $active)
    {
        return $this->produitRepository->update($id, ['actif' => $active]);
    }

    /**
     * Gestion du stock: décrémenter sur commande confirmée
     */
    public function decrementStock(int $id, int $quantity)
    {
        $produit = $this->produitRepository->findById($id);
        
        if (!$produit) {
            throw new Exception("Produit introuvable.");
        }

        if ($produit->quantite_stock < $quantity) {
            throw new Exception("Stock insuffisant pour le produit: {$produit->nom}");
        }

        $newStock = $produit->quantite_stock - $quantity;
        
        return $this->produitRepository->update($id, ['quantite_stock' => $newStock]);
    }

    /**
     * Récupérer un produit par slug
     */
    public function getProduitBySlug(string $slug)
    {
        return $this->produitRepository->findBySlug($slug);
    }

    /**
     * Liste des produits par catégorie
     */
    public function getProduitsByCategory(int $categoryId)
    {
        return $this->produitRepository->findByCategory($categoryId);
    }

    /**
     * Validation pour la création
     */
    protected function validateProduit(array $data)
    {
        $validator = Validator::make($data, [
            'nom' => 'required|string|max:255',
            'categorie_id' => 'required|exists:categories,id',
            'type_produit' => 'required|in:pc,portable,composant,peripherique,service',
            'prix' => 'required|numeric|min:0',
            'quantite_stock' => 'required|integer|min:0',
            'slug' => 'nullable|string|max:190|unique:produits,slug',
            'reference' => 'nullable|string|max:80|unique:produits,reference',
            'actif' => 'boolean'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
