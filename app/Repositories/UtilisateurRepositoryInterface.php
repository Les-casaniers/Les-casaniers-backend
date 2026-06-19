<?php

namespace App\Repositories;

interface UtilisateurRepositoryInterface
{
    public function getAll();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function findByEmail($email);
    public function getAllPaginated($perPage = 10, $search = '', $statut = '');
    public function search($query = '', $statut = '');
    public function bulkUpdate(array $ids, array $data);
    public function bulkDelete(array $ids);
}
