<?php

namespace App\Repositories\Sales;

use App\Models\Facture;

interface FactureRepositoryInterface
{
    public function all();
    public function allByUser(int $userId);
    public function find(int $id);
    public function findForUser(int $id, int $userId);
    public function findByCommandeId(int $commandeId);
    public function findByCommandeUuid(string $uuid);
    public function create(array $data): Facture;
    public function update(int $id, array $data): Facture;
    public function nextReference(): string;
}
