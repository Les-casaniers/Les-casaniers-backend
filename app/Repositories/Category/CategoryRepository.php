<?php

namespace App\Repositories\Category;

use App\Models\Category;

class CategoryRepository implements CategoryRepositoryInterface
{
    protected $model;

    public function __construct(Category $category)
    {
        $this->model = $category;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function getRoots()
    {
        return $this->model->whereNull('parent_id')->orderBy('ordre_tri')->get();
    }

    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $category = $this->findById($id);
        if ($category) {
            $category->update($data);
            return $category;
        }
        return null;
    }

    public function delete(int $id)
    {
        $category = $this->findById($id);
        if ($category) {
            return $category->delete();
        }
        return false;
    }

    public function getTree()
    {
        return $this->model->whereNull('parent_id')
            ->with('enfants')
            ->orderBy('ordre_tri')
            ->get();
    }
}
