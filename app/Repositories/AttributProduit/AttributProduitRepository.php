<?php

namespace App\Repositories\AttributProduit;

use App\Models\AttributProduit;

class AttributProduitRepository implements AttributProduitRepositoryInterface
{
    protected $model;

    public function __construct(AttributProduit $attributProduit)
    {
        $this->model = $attributProduit;
    }

    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    public function findByProduit(int $produitId)
    {
        return $this->model->where('produit_id', $produitId)->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $attribute = $this->findById($id);
        if ($attribute) {
            $attribute->update($data);
            return $attribute;
        }
        return null;
    }

    public function delete(int $id)
    {
        $attribute = $this->findById($id);
        if ($attribute) {
            return $attribute->delete();
        }
        return false;
    }

    public function deleteByProduit(int $produitId)
    {
        return $this->model->where('produit_id', $produitId)->delete();
    }
}
