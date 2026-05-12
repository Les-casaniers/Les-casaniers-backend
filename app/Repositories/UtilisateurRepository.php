<?php

namespace App\Repositories;

use App\Models\Utilisateur;

class UtilisateurRepository implements UtilisateurRepositoryInterface
{
    public function getAll()
    {
        return Utilisateur::all();
    }

    public function findById($id)
    {
        return Utilisateur::find($id);
    }

    public function create(array $data)
    {
        return Utilisateur::create($data);
    }

    public function update($id, array $data)
    {
        $utilisateur = Utilisateur::find($id);

        if ($utilisateur) {
            $utilisateur->update($data);
            return $utilisateur;
        }

        return null;
    }

    public function delete($id)
    {
        $utilisateur = Utilisateur::find($id);

        if ($utilisateur) {
            return $utilisateur->delete();
        }

        return false;
    }

    public function findByEmail($email)
    {
        return Utilisateur::where('email', $email)->first();
    }
}
