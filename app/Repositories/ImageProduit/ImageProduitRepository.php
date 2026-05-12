<?php

namespace App\Repositories\ImageProduit;

use App\Models\ImageProduit;
use Illuminate\Support\Facades\DB;

class ImageProduitRepository implements ImageProduitRepositoryInterface
{
    protected $model;

    public function __construct(ImageProduit $imageProduit)
    {
        $this->model = $imageProduit;
    }

    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    public function findByProduit(int $produitId)
    {
        return $this->model->where('produit_id', $produitId)->orderBy('ordre')->get();
    }

    public function create(array $data)
    {
        // If order is not provided, put it at the end
        if (!isset($data['ordre'])) {
            $maxOrder = $this->model->where('produit_id', $data['produit_id'])->max('ordre');
            $data['ordre'] = ($maxOrder !== null) ? $maxOrder + 1 : 0;
        }

        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $image = $this->findById($id);
        if ($image) {
            $image->update($data);
            return $image;
        }
        return null;
    }

    public function delete(int $id)
    {
        $image = $this->findById($id);
        if ($image) {
            return $image->delete();
        }
        return false;
    }

    public function updateOrder(int $produitId, array $imageOrders)
    {
        return DB::transaction(function () use ($imageOrders) {
            foreach ($imageOrders as $id => $order) {
                $this->model->where('id', $id)->update(['ordre' => $order]);
            }
            return true;
        });
    }
}
