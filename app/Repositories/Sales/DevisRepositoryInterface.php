<?php

namespace App\Repositories\Sales;

interface DevisRepositoryInterface
{
    public function all();
    public function allByStatus(string $statut);
    public function allByUser(int $userId);
    public function allByUserAndStatus(int $userId, string $statut);
    public function findByIdForUser(int $id, int $userId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
