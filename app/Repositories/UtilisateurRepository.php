<?php

namespace App\Repositories;

use App\Models\Utilisateur;

class UtilisateurRepository implements UtilisateurRepositoryInterface
{
    public function getAll()
    {
        return Utilisateur::with('adresses')
            ->orderByDesc('date_creation')
            ->get();
    }

    public function findById($id)
    {
        return Utilisateur::with('adresses')->find($id);
    }

    public function findByEmail($email)
    {
        return Utilisateur::where('email', $email)->first();
    }

    public function create(array $data)
    {
        return Utilisateur::create($data);
    }

    public function update($id, array $data)
    {
        $utilisateur = Utilisateur::find($id);

        if (!$utilisateur) {
            return null;
        }

        $utilisateur->update($data);

        return $utilisateur->fresh();
    }

    public function delete($id)
    {
        $utilisateur = Utilisateur::find($id);

        if ($utilisateur) {
            return $utilisateur->delete();
        }

        return false;
    }

    public function getAllPaginated($perPage = 10, $search = '', $statut = '')
    {
        $query = Utilisateur::with('adresses');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('prenom', 'LIKE', "%{$search}%")
                    ->orWhere('nom', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('telephone', 'LIKE', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        if ($statut !== '' && $statut !== null && $statut !== 'all') {
            $query->where('statut', $statut);
        }

        return $query->orderByDesc('date_creation')->paginate($perPage);
    }

    public function search($query = '', $statut = '')
    {
        $q = Utilisateur::with('adresses');

        if (!empty($query)) {
            $q->where(function ($w) use ($query) {
                $w->where('prenom', 'LIKE', "%{$query}%")
                    ->orWhere('nom', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('telephone', 'LIKE', "%{$query}%");
            });
        }

        if ($statut !== '' && $statut !== null && $statut !== 'all') {
            $q->where('statut', $statut);
        }

        return $q->orderByDesc('date_creation')->get();
    }

    public function bulkUpdate(array $ids, array $data)
    {
        return Utilisateur::whereIn('id', $ids)->update($data);
    }

    public function bulkDelete(array $ids)
    {
        return Utilisateur::whereIn('id', $ids)->delete();
    }
}
