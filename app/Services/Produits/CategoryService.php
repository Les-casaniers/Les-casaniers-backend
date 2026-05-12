<?php

namespace App\Services\Produits;

use App\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class CategoryService
{
    protected $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * CRUD Back-Office: Créer une catégorie
     */
    public function createCategory(array $data)
    {
        $this->validateCategory($data);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['nom']);
        }

        // Vérifier la cohérence du type avec le parent
        if (!empty($data['parent_id'])) {
            $parent = $this->categoryRepository->findById($data['parent_id']);
            if ($parent && $parent->type !== $data['type']) {
                // On peut forcer le type du parent ou lever une alerte
                $data['type'] = $parent->type;
            }
        }

        return $this->categoryRepository->create($data);
    }

    /**
     * CRUD Back-Office: Mettre à jour une catégorie
     */
    public function updateCategory(int $id, array $data)
    {
        $this->validateCategory($data, $id);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['nom']);
        }

        // Vérifier la cohérence du type
        if (!empty($data['parent_id'])) {
            $parent = $this->categoryRepository->findById($data['parent_id']);
            if ($parent && $parent->type !== $data['type']) {
                $data['type'] = $parent->type;
            }
        }

        return $this->categoryRepository->update($id, $data);
    }

    /**
     * CRUD Back-Office: Supprimer une catégorie
     */
    public function deleteCategory(int $id)
    {
        return $this->categoryRepository->delete($id);
    }

    /**
     * Route front: Récupérer une catégorie par son slug
     */
    public function getCategoryBySlug(string $slug)
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    /**
     * Fil d’Ariane (breadcrumbs)
     */
    public function getBreadcrumbs(int $categoryId)
    {
        $breadcrumbs = [];
        $category = $this->categoryRepository->findById($categoryId);

        while ($category) {
            array_unshift($breadcrumbs, [
                'nom' => $category->nom,
                'slug' => $category->slug
            ]);
            $category = $category->parent_id ? $this->categoryRepository->findById($category->parent_id) : null;
        }

        return $breadcrumbs;
    }

    /**
     * Récupérer l'arborescence pour le menu (par type)
     */
    public function getMenuByType(string $type)
    {
        // On pourrait ajouter une méthode spécifique au repository ou filtrer ici
        return $this->categoryRepository->getRoots()
            ->where('type', $type)
            ->load('enfants');
    }

    /**
     * Récupérer toutes les catégories
     */
    public function getAllCategories()
    {
        return $this->categoryRepository->getAll();
    }

    /**
     * Organiser l'ordre de tri
     */
    public function updateOrder(array $orders)
    {
        foreach ($orders as $id => $ordre) {
            $this->categoryRepository->update($id, ['ordre_tri' => $ordre]);
        }
        return true;
    }

    /**
     * Validation des données
     */
    protected function validateCategory(array $data, int $id = null)
    {
        $rules = [
            'nom' => 'required|string|max:190',
            'slug' => 'nullable|string|max:190|unique:categories,slug' . ($id ? ",$id" : ""),
            'type' => 'required|in:pro,gaming,composants,peripheriques,services,guides',
            'parent_id' => 'nullable|exists:categories,id',
            'ordre_tri' => 'integer'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
