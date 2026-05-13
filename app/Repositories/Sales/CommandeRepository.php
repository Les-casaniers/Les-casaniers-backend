<?php

namespace App\Repositories\Sales;

use App\Models\Commande;

class CommandeRepository implements CommandeRepositoryInterface
{
    public function all()
    {
        return Commande::with(['utilisateur', 'panier', 'devis'])
            ->latest()
            ->get();
    }

    public function allByStatus(string $statut)
    {
        return Commande::with(['utilisateur', 'panier', 'devis'])
            ->where('statut', $statut)
            ->latest()
            ->get();
    }

    public function allByUser(int $userId)
    {
        return Commande::with(['utilisateur', 'panier', 'devis'])
            ->where('utilisateur_id', $userId)
            ->latest()
            ->get();
    }

    public function allByUserAndStatus(int $userId, string $statut)
    {
        return Commande::with(['utilisateur', 'panier', 'devis'])
            ->where('utilisateur_id', $userId)
            ->where('statut', $statut)
            ->latest()
            ->get();
    }

    public function create(array $data)
    {
        return Commande::create($data);
    }

    public function findByUuid(string $uuid)
    {
        return Commande::with(['utilisateur', 'panier', 'devis'])
            ->where('commande_uuid', $uuid)
            ->get();
    }

    public function findByUuidForUser(string $uuid, int $userId)
    {
        return Commande::with(['utilisateur', 'panier', 'devis'])
            ->where('commande_uuid', $uuid)
            ->where('utilisateur_id', $userId)
            ->first();
    }

    public function updateByUuid(string $uuid, array $data)
    {
        Commande::where('commande_uuid', $uuid)->update($data);

        return $this->findByUuid($uuid);
    }

    public function findByUuidWithLock(string $uuid)
    {
        return Commande::with(['utilisateur'])
            ->where('commande_uuid', $uuid)
            ->lockForUpdate()
            ->get();
    }
}
