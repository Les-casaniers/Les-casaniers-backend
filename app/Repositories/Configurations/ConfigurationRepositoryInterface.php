<?php

namespace App\Repositories\Configurations;

interface ConfigurationRepositoryInterface
{
    public function getAll(array $filters = []);

    public function findById(int $id);

    public function create(array $data);

    public function createMany(array $rows);

    public function update(int $id, array $data);

    public function delete(int $id);
}
