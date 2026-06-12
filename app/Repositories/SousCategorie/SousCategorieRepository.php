<?php

namespace App\Repositories\SousCategorie;

use App\Models\SousCategorie;

class SousCategorieRepository implements SousCategorieRepositoryInterface
{
    protected $model;

    public function __construct(SousCategorie $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with('categorie')->get();
    }

    public function findById(int $id)
    {
        return $this->model->with('categorie')->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $sousCategorie = $this->findById($id);
        if ($sousCategorie) {
            $sousCategorie->update($data);
            return $sousCategorie;
        }
        return null;
    }

    public function delete(int $id)
    {
        $sousCategorie = $this->findById($id);
        if ($sousCategorie) {
            return $sousCategorie->delete();
        }
        return false;
    }

    public function getByCategorie(int $categorieId)
    {
        return $this->model->where('id_categorie', $categorieId)->get();
    }
}
