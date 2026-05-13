<?php

namespace App\Repositories\Configurations;

interface ConfigurationRepositoryInterface
{
    public function getAllByUser(?int $userId);

    public function findById(int $id);

    public function findByIdForUser(int $id, ?int $userId);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);
}
