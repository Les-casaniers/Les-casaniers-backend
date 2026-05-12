<?php

namespace App\Repositories\Produit;

use App\Models\Produit;

class ProduitRepository implements ProduitRepositoryInterface
{
    protected $model;

    public function __construct(Produit $produit)
    {
        $this->model = $produit;
    }

    public function getAll(array $filters = [])
    {
        $query = $this->model->with(['categorie', 'images']);

        if (isset($filters['categorie_id'])) {
            $query->where('categorie_id', $filters['categorie_id']);
        }

        if (isset($filters['actif'])) {
            $query->where('actif', $filters['actif']);
        }

        if (isset($filters['type_produit'])) {
            $query->where('type_produit', $filters['type_produit']);
        }

        return $query->get();
    }

    public function findById(int $id)
    {
        return $this->model->with(['categorie', 'images', 'attributs'])->find($id);
    }

    public function findBySlug(string $slug)
    {
        return $this->model->with(['categorie', 'images', 'attributs'])
            ->where('slug', $slug)
            ->first();
    }

    public function findByCategory(int $categoryId)
    {
        return $this->model->where('categorie_id', $categoryId)
            ->with(['images'])
            ->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $produit = $this->findById($id);
        if ($produit) {
            $produit->update($data);
            return $produit;
        }
        return null;
    }

    public function delete(int $id)
    {
        $produit = $this->findById($id);
        if ($produit) {
            return $produit->delete();
        }
        return false;
    }

    public function search(string $term)
    {
        return $this->model->where('nom', 'LIKE', "%{$term}%")
            ->orWhere('reference', 'LIKE', "%{$term}%")
            ->with(['categorie', 'images'])
            ->get();
    }

    public function getLatest(int $limit = 10)
    {
        return $this->model->orderBy('date_creation', 'DESC')
            ->limit($limit)
            ->with(['categorie', 'images'])
            ->get();
    }
}
