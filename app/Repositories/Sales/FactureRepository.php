<?php

namespace App\Repositories\Sales;

use App\Models\Facture;

class FactureRepository implements FactureRepositoryInterface
{
    public function all()
    {
        return Facture::with(['commande.utilisateur'])
            ->latest('id')
            ->get();
    }

    public function allByUser(int $userId)
    {
        return Facture::with(['commande.utilisateur'])
            ->whereHas('commande', function ($query) use ($userId) {
                $query->where('utilisateur_id', $userId);
            })
            ->latest('id')
            ->get();
    }

    public function find(int $id)
    {
        return Facture::with(['commande.utilisateur'])->findOrFail($id);
    }

    public function findForUser(int $id, int $userId)
    {
        return Facture::with(['commande.utilisateur'])
            ->where('id', $id)
            ->whereHas('commande', function ($query) use ($userId) {
                $query->where('utilisateur_id', $userId);
            })
            ->first();
    }

    public function findByCommandeId(int $commandeId)
    {
        return Facture::with(['commande.utilisateur'])
            ->where('commande_id', $commandeId)
            ->first();
    }

    public function findByCommandeUuid(string $uuid)
    {
        return Facture::with(['commande.utilisateur'])
            ->whereHas('commande', function ($query) use ($uuid) {
                $query->where('commande_uuid', $uuid);
            })
            ->first();
    }

    public function create(array $data): Facture
    {
        return Facture::create($data);
    }

    public function update(int $id, array $data): Facture
    {
        $facture = Facture::findOrFail($id);
        $facture->update($data);
        return $this->find($id);
    }

    public function nextReference(): string
    {
        $last = Facture::query()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $next = $last ? ((int) preg_replace('/\D/', '', $last->facture_ref) + 1) : 1;

        return 'FAC-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
