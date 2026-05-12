<?php

namespace App\Repositories;

use App\Models\Admin;

interface AdminRepositoryInterface
{
    public function getAll();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function findByEmail($email);
}