<?php

namespace App\Repositories\Category;

interface CategoryRepositoryInterface
{
    public function getAll();
    public function getRoots();
    public function findById(int $id);
    public function findBySlug(string $slug);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getTree();
}
