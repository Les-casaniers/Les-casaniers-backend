<?php

namespace App\Repositories\AvisClients;

use App\Models\AvisClient;

class AvisClientRepository implements AvisClientRepositoryInterface
{
    public function create(array $data)
    {
        return AvisClient::create($data);
    }

    public function existsByProduitAndUser(int $produitId, int $utilisateurId): bool
    {
        return AvisClient::where('produit_id', $produitId)
            ->where('utilisateur_id', $utilisateurId)
            ->exists();
    }

    public function getPublishedByProduit(int $produitId)
    {
        return AvisClient::with(['utilisateur:id,nom,prenom'])
            ->where('produit_id', $produitId)
            ->where('publie', true)
            ->latest('id')
            ->get();
    }

    public function findById(int $id)
    {
        return AvisClient::find($id);
    }

    public function findByIdWithRelations(int $id)
    {
        return AvisClient::with(['produit', 'utilisateur'])->find($id);
    }

    public function getByUser(int $utilisateurId)
    {
        return AvisClient::with(['produit'])
            ->where('utilisateur_id', $utilisateurId)
            ->latest('id')
            ->get();
    }

    public function update(int $id, array $data)
    {
        $avis = AvisClient::findOrFail($id);
        $avis->update($data);

        return $avis->fresh(['produit', 'utilisateur']);
    }

    public function delete(int $id): bool
    {
        return AvisClient::where('id', $id)->delete() > 0;
    }

    public function getAdminList(array $filters = [])
    {
        $query = AvisClient::with(['produit', 'utilisateur']);

        if (array_key_exists('publie', $filters) && !is_null($filters['publie'])) {
            $query->where('publie', (bool) $filters['publie']);
        }

        if (!empty($filters['produit_id'])) {
            $query->where('produit_id', (int) $filters['produit_id']);
        }

        if (!empty($filters['note'])) {
            $query->where('note', (int) $filters['note']);
        }

        if (!empty($filters['keyword'])) {
            $query->where('corps', 'like', '%' . $filters['keyword'] . '%');
        }

        return $query->latest('id')->get();
    }

    public function getAverageNoteByProduit(int $produitId): float
    {
        return (float) AvisClient::where('produit_id', $produitId)
            ->where('publie', true)
            ->avg('note');
    }

    public function getStatsByProduit(int $produitId): array
    {
        $base = AvisClient::where('produit_id', $produitId)->where('publie', true);
        $total = (clone $base)->count();
        $repartition = [];

        for ($note = 5; $note >= 1; $note--) {
            $repartition[(string) $note] = (clone $base)->where('note', $note)->count();
        }

        return [
            'total' => $total,
            'repartition' => $repartition,
        ];
    }

    public function search(array $filters = [])
    {
        $query = AvisClient::with(['produit', 'utilisateur']);

        if (!empty($filters['produit_id'])) {
            $query->where('produit_id', (int) $filters['produit_id']);
        }

        if (!empty($filters['note'])) {
            $query->where('note', (int) $filters['note']);
        }

        if (!empty($filters['keyword'])) {
            $query->where('corps', 'like', '%' . $filters['keyword'] . '%');
        }

        if (array_key_exists('publie', $filters) && !is_null($filters['publie'])) {
            $query->where('publie', (bool) $filters['publie']);
        }

        return $query->latest('id')->get();
    }

    public function getLatest(int $limit = 10)
    {
        return AvisClient::with(['produit', 'utilisateur'])
            ->where('publie', true)
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
