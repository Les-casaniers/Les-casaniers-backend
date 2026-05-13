<?php

namespace App\Repositories\Sales;

interface CommandeRepositoryInterface
{
    public function all();
    public function allByStatus(string $statut);
    public function allByUser(int $userId);
    public function allByUserAndStatus(int $userId, string $statut);
    public function create(array $data);
    public function findByUuid(string $uuid);
    public function findByUuidForUser(string $uuid, int $userId);
    public function updateByUuid(string $uuid, array $data);

    /**
     * Récupère les items d'une commande par UUID avec verrou pessimiste.
     */
    public function findByUuidWithLock(string $uuid);
}
