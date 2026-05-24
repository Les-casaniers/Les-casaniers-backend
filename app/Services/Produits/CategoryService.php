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

        return $this->categoryRepository->create($data);
    }

    /**
     * CRUD Back-Office: Mettre à jour une catégorie
     */
    public function updateCategory(int $id, array $data)
    {
        $this->validateCategory($data, $id);

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

    public function getAllCategories()
    {
        return $this->categoryRepository->getAll();
    }

    protected function validateCategory(array $data, int $id = null)
    {
        $rules = [
            'nom' => 'required|string|max:190',
            'parent_id' => 'nullable|exists:categories,id',
            'code' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
