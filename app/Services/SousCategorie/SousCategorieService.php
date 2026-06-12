<?php

namespace App\Services\SousCategorie;

use App\Repositories\SousCategorie\SousCategorieRepositoryInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class SousCategorieService
{
    protected $repository;

    public function __construct(SousCategorieRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function getById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function create(array $data)
    {
        $this->validate($data);
        return $this->repository->create($data);
    }

    public function update(int $id, array $data)
    {
        $this->validate($data, true);
        return $this->repository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }

    protected function validate(array $data, bool $isUpdate = false)
    {
        $rules = [
            'id_categorie' => 'required|exists:categories,id',
            'nom' => 'required|string|max:255',
        ];

        if ($isUpdate) {
            $rules['id_categorie'] = 'sometimes|exists:categories,id';
            $rules['nom'] = 'sometimes|string|max:255';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
