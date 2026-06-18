<?php

namespace App\Repositories\ImageProduit;

use App\Models\ImageProduit;
use App\Repositories\BaseRepository;

class ImageProduitRepository extends BaseRepository implements ImageProduitRepositoryInterface
{
    public function __construct(ImageProduit $model)
    {
        parent::__construct($model);
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
}