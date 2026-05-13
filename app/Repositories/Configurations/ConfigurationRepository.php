<?php

namespace App\Repositories\Configurations;

use App\Models\Configuration;

class ConfigurationRepository implements ConfigurationRepositoryInterface
{
    public function getAllByUser(?int $userId)
    {
        return Configuration::with(['produit'])
            ->where('utilisateur_id', $userId)
            ->latest()
            ->get();
    }

    public function findById(int $id)
    {
        return Configuration::findOrFail($id);
    }

    public function findByIdForUser(int $id, ?int $userId)
    {
        return Configuration::where('id', $id)
            ->where('utilisateur_id', $userId)
            ->first();
    }

    public function create(array $data)
    {
        return Configuration::create($data);
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
