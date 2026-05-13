<?php

namespace App\Repositories\Adresse;

interface AdresseRepositoryInterface
{
    public function listByUser(int $utilisateurId);

    public function findByIdForUser(int $id, int $utilisateurId);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): bool;

    public function clearDefaultExpeditionForUser(int $utilisateurId, ?int $exceptId = null): int;

    public function clearDefaultFacturationForUser(int $utilisateurId, ?int $exceptId = null): int;

    public function getDefaultExpeditionByUser(int $utilisateurId);
}
