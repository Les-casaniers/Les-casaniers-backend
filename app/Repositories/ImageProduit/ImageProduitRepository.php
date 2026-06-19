<?php

namespace App\Repositories\ImageProduit;

use App\Models\ImageProduit;

class ImageProduitRepository implements ImageProduitRepositoryInterface
{
    protected $model;

    public function __construct(ImageProduit $model)
    {
        $this->model = $model;
    }

    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $record = $this->model->find($id);
        if (!$record) {
            return null;
        }
        $record->fill($data);
        $record->save();
        return $record;
    }

    public function delete(int $id)
    {
        $record = $this->model->find($id);
        if (!$record) {
            return 0;
        }
        return $record->delete();
    }

    public function updateOrder(int $produitId, array $orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            $this->model->where('produit_id', $produitId)
                ->where('id', $id)
                ->update(['ordre' => $index]);
        }
        return $this->findByProduit($produitId);
    }

    public function findByProduit(int $produitId)
    {
        return $this->model->where('produit_id', $produitId)
            ->orderBy('ordre', 'asc')
            ->get();
    }

    public function findMainImage(int $produitId)
    {
        return $this->model->where('produit_id', $produitId)
            ->where('ordre', 0)
            ->first();
    }

    public function deleteByProduit(int $produitId)
    {
        return $this->model->where('produit_id', $produitId)->delete();
    }

    public function updateOrder(int $produitId, array $imageOrders)
    {
        foreach ($imageOrders as $imageId => $ordre) {
            $this->model->where('id', $imageId)
                ->where('produit_id', $produitId)
                ->update(['ordre' => $ordre]);
        }
        return true;
    }
}