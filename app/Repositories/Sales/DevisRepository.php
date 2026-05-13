<?php

namespace App\Repositories\Sales;

use App\Models\Devis;

class DevisRepository implements DevisRepositoryInterface
{
    public function allByUser(int $userId)
    {
        return Devis::with(['utilisateur', 'panier'])
            ->where('utilisateur_id', $userId)
            ->latest('id')
            ->get();
    }

    public function allByUserAndStatus(int $userId, string $statut)
    {
        return Devis::with(['utilisateur', 'panier'])
            ->where('utilisateur_id', $userId)
            ->where('statut', $statut)
            ->latest('id')
            ->get();
    }

    public function findByIdForUser(int $id, int $userId)
    {
        return Devis::with(['utilisateur', 'panier'])
            ->where('id', $id)
            ->where('utilisateur_id', $userId)
            ->first();
    }

    public function create(array $data)
    {
        return Devis::create($data);
    }

    public function update(int $id, array $data)
    {
        $devis = Devis::findOrFail($id);
        $devis->update($data);
        return $devis;
    }

    public function delete(int $id)
    {
        return Devis::destroy($id);
    }
}
