<?php

namespace App\Repositories\Adresse;

use App\Models\AdresseUtilisateur;

class AdresseRepository implements AdresseRepositoryInterface
{
    public function listByUser(int $utilisateurId)
    {
        return AdresseUtilisateur::where('utilisateur_id', $utilisateurId)
            ->orderBy('par_defaut_expedition', 'desc')
            ->orderBy('date_creation', 'desc')
            ->get();
    }

    public function findByIdForUser(int $id, int $utilisateurId)
    {
        return AdresseUtilisateur::where('utilisateur_id', $utilisateurId)
            ->where('id', $id)
            ->first();
    }

    public function create(array $data)
    {
        return AdresseUtilisateur::create($data);
    }

    public function update(int $id, array $data)
    {
        $adresse = AdresseUtilisateur::findOrFail($id);
        $adresse->update($data);

        return $adresse->fresh();
    }

    public function delete(int $id): bool
    {
        return AdresseUtilisateur::where('id', $id)->delete() > 0;
    }

    public function clearDefaultExpeditionForUser(int $utilisateurId, ?int $exceptId = null): int
    {
        $query = AdresseUtilisateur::where('utilisateur_id', $utilisateurId)
            ->where('par_defaut_expedition', true);

        if (!is_null($exceptId)) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->update(['par_defaut_expedition' => false]);
    }

    public function clearDefaultFacturationForUser(int $utilisateurId, ?int $exceptId = null): int
    {
        $query = AdresseUtilisateur::where('utilisateur_id', $utilisateurId)
            ->where('par_defaut_facturation', true);

        if (!is_null($exceptId)) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->update(['par_defaut_facturation' => false]);
    }

    public function getDefaultExpeditionByUser(int $utilisateurId)
    {
        return AdresseUtilisateur::where('utilisateur_id', $utilisateurId)
            ->where('par_defaut_expedition', true)
            ->first();
    }
}
