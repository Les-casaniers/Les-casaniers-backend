<?php

namespace App\Repositories\Configurations;

use App\Models\Configuration;

class ConfigurationRepository implements ConfigurationRepositoryInterface
{
    public function getAll(array $filters = [])
    {
        $query = Configuration::with(['produit'])->latest('date_creation');

        if (!empty($filters['produit_id'])) {
            $query->where('produit_id', $filters['produit_id']);
        }

        return $query->get();
    }

    public function findById(int $id)
    {
        return Configuration::find($id);
    }

    public function create(array $data)
    {
        return Configuration::create($data);
    }

    public function createMany(array $rows)
    {
        return collect($rows)->map(fn (array $row) => Configuration::create($row))->values();
    }

    public function update(int $id, array $data)
    {
        $config = Configuration::findOrFail($id);
        $config->update($data);
        return $config;
    }

    public function delete(int $id)
    {
        return Configuration::destroy($id);
    }
}
