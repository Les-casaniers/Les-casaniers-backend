<?php

namespace App\Repositories\Guides;

interface GuideRepositoryInterface
{
    public function paginate(array $filters = [], bool $admin = false);
    public function recent(int $limit = 4);
    public function popular(int $limit = 4);
    public function featured(int $limit = 6);
    public function byCategory(string $categorie, int $limit = 6);
    public function categories(): array;
    public function findPublicById(int $id);
    public function findBySlug(string $slug);
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
}
