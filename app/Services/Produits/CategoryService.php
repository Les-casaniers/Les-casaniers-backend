<?php

namespace App\Services\Produits;

use App\Repositories\Category\CategoryRepositoryInterface;
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

        if (!empty($data['parent_id'])) {
            $parent = $this->categoryRepository->findById($data['parent_id']);
            if ($parent && $parent->type !== $data['type']) {
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
     * Récupérer une catégorie par ID
     */
    public function getCategoryById(int $id)
    {
        return $this->categoryRepository->findById($id);
    }

    /**
     * Fil d'Ariane (breadcrumbs)
     */
    public function getBreadcrumbs(int $categoryId)
    {
        $breadcrumbs = [];
        $category = $this->categoryRepository->findById($categoryId);

        while ($category) {
            array_unshift($breadcrumbs, [
                'nom' => $category->nom,
                'id' => $category->id,
            ]);
            $category = $category->parent_id ? $this->categoryRepository->findById($category->parent_id) : null;
        }

        return $breadcrumbs;
    }

    public function getMenuByType(string $type)
    {
        return $this->categoryRepository->getRoots()
            ->where('type', $type)
            ->load('enfants');
    }

    public function getAllCategories()
    {
        return $this->categoryRepository->getAll();
    }

    public function updateOrder(array $orders)
    {
        foreach ($orders as $id => $ordre) {
            $this->categoryRepository->update($id, ['ordre_tri' => $ordre]);
        }
        return true;
    }

    protected function validateCategory(array $data, int $id = null)
    {
        $rules = [
            'nom' => 'required|string|max:190',
            'type' => 'required|in:pro,gaming,composants,peripheriques,services,guides',
            'parent_id' => 'nullable|exists:categories,id',
            'ordre_tri' => 'integer',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
