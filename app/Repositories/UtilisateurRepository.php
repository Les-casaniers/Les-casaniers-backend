<?php

namespace App\Repositories;

use App\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;

class UtilisateurRepository implements UtilisateurRepositoryInterface
{
    /**
     * Récupérer tous les utilisateurs
     */
    public function getAll()
    {
        return Utilisateur::with('adresses')
            ->orderByDesc('date_creation')
            ->get();
    }

    /**
     * Récupérer un utilisateur par ID
     */
    public function findById($id)
    {
        return Utilisateur::with('adresses')->find($id);
    }

    /**
     * Récupérer un utilisateur par email
     */
    public function findByEmail($email)
    {
        return Utilisateur::where('email', $email)->first();
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function create(array $data)
    {
        // Hash du mot de passe si présent
        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = Hash::make($data['mot_de_passe']);
        }
        
        return Utilisateur::create($data);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update($id, array $data)
    {
        $utilisateur = Utilisateur::find($id);

        if ($utilisateur) {
            // Hash du mot de passe si présent
            if (isset($data['mot_de_passe'])) {
                $data['mot_de_passe'] = Hash::make($data['mot_de_passe']);
            }
            
            $utilisateur->update($data);
            return $utilisateur->fresh();
        }

        return null;
    }

    /**
     * Supprimer un utilisateur
     */
    public function delete($id)
    {
        $utilisateur = Utilisateur::find($id);

        if ($utilisateur) {
            return $utilisateur->delete();
        }

        return false;
    }

    // ============================================
    // NOUVELLES METHODES POUR LE FRONTEND
    // ============================================

    /**
     * Récupérer tous les utilisateurs avec pagination
     */
    public function getAllPaginated($perPage = 10, $search = '', $statut = '')
    {
        $query = Utilisateur::with('adresses');

        // Recherche
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('prenom', 'LIKE', "%{$search}%")
                  ->orWhere('nom', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('telephone', 'LIKE', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        // Filtrage par statut
        if ($statut !== '' && $statut !== null && $statut !== 'all') {
            $query->where('statut', $statut);
        }

        // Tri par date de création décroissante
        $query->orderByDesc('date_creation');

        return $query->paginate($perPage);
    }

    /**
     * Rechercher des utilisateurs
     */
    public function search($query = '', $statut = '')
    {
        $q = Utilisateur::with('adresses');

        if (!empty($query)) {
            $q->where(function($w) use ($query) {
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

    /**
     * Mise à jour en masse
     */
    public function bulkUpdate(array $ids, array $data)
    {
        return Utilisateur::whereIn('id', $ids)->update($data);
    }

    /**
     * Suppression en masse
     */
    public function bulkDelete(array $ids)
    {
        return Utilisateur::whereIn('id', $ids)->delete();
    }
}