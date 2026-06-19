<?php

namespace App\Services\Adresse;

use App\Repositories\Adresse\AdresseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdresseService
{
    public function __construct(
        private readonly AdresseRepositoryInterface $adresseRepository
    ) {
    }

    public function list(int $utilisateurId)
    {
        return $this->adresseRepository->listByUser($utilisateurId);
    }

    public function show(int $id, int $utilisateurId)
    {
        $adresse = $this->adresseRepository->findByIdForUser($id, $utilisateurId);
        if (!$adresse) {
            throw ValidationException::withMessages([
                'id' => ['Adresse introuvable.'],
            ]);
        }

        return $adresse;
    }

    public function create(int $utilisateurId, array $payload)
    {
        return DB::transaction(function () use ($utilisateurId, $payload) {
            if (!empty($payload['par_defaut_expedition'])) {
                $this->adresseRepository->clearDefaultExpeditionForUser($utilisateurId);
            }

            if (!empty($payload['par_defaut_facturation'])) {
                $this->adresseRepository->clearDefaultFacturationForUser($utilisateurId);
            }

            // ✅ AJOUT DES NOUVEAUX CHAMPS
            return $this->adresseRepository->create([
                'utilisateur_id' => $utilisateurId,
                'etiquette' => $payload['etiquette'] ?? null,
                'nom_complet' => $payload['nom_complet'],
                'telephone' => $payload['telephone'] ?? null,
                'adresse_ligne1' => $payload['adresse_ligne1'],
                'adresse_ligne2' => $payload['adresse_ligne2'] ?? null,
                'ville' => $payload['ville'],
                'region' => $payload['region'] ?? null,
                'code_postal' => $payload['code_postal'] ?? null,
                'pays' => $payload['pays'],
                'par_defaut_expedition' => (bool) ($payload['par_defaut_expedition'] ?? false),
                'par_defaut_facturation' => (bool) ($payload['par_defaut_facturation'] ?? false),
                // ✅ NOUVEAUX CHAMPS
                'image_adress' => $payload['image_adress'] ?? null,
                'latitude' => $payload['latitude'] ?? null,
                'longitude' => $payload['longitude'] ?? null,
                'date_creation' => now(),
                'date_modification' => now(),
            ]);
        });
    }

    public function update(int $id, int $utilisateurId, array $payload)
    {
        return DB::transaction(function () use ($id, $utilisateurId, $payload) {
            $adresse = $this->adresseRepository->findByIdForUser($id, $utilisateurId);
            if (!$adresse) {
                throw ValidationException::withMessages([
                    'id' => ['Adresse introuvable.'],
                ]);
            }

            if (!empty($payload['par_defaut_expedition'])) {
                $this->adresseRepository->clearDefaultExpeditionForUser($utilisateurId, $id);
            }

            if (!empty($payload['par_defaut_facturation'])) {
                $this->adresseRepository->clearDefaultFacturationForUser($utilisateurId, $id);
            }

            // ✅ AJOUT DES NOUVEAUX CHAMPS DANS LA MISE À JOUR
            $payload['date_modification'] = now();

            return $this->adresseRepository->update($id, [
                'etiquette' => $payload['etiquette'] ?? $adresse->etiquette,
                'nom_complet' => $payload['nom_complet'] ?? $adresse->nom_complet,
                'telephone' => $payload['telephone'] ?? $adresse->telephone,
                'adresse_ligne1' => $payload['adresse_ligne1'] ?? $adresse->adresse_ligne1,
                'adresse_ligne2' => $payload['adresse_ligne2'] ?? $adresse->adresse_ligne2,
                'ville' => $payload['ville'] ?? $adresse->ville,
                'region' => $payload['region'] ?? $adresse->region,
                'code_postal' => $payload['code_postal'] ?? $adresse->code_postal,
                'pays' => $payload['pays'] ?? $adresse->pays,
                'par_defaut_expedition' => (bool) ($payload['par_defaut_expedition'] ?? $adresse->par_defaut_expedition),
                'par_defaut_facturation' => (bool) ($payload['par_defaut_facturation'] ?? $adresse->par_defaut_facturation),
                // ✅ NOUVEAUX CHAMPS
                'image_adress' => $payload['image_adress'] ?? $adresse->image_adress,
                'latitude' => $payload['latitude'] ?? $adresse->latitude,
                'longitude' => $payload['longitude'] ?? $adresse->longitude,
                'date_modification' => now(),
            ]);
        });
    }

    public function delete(int $id, int $utilisateurId): bool
    {
        $adresse = $this->adresseRepository->findByIdForUser($id, $utilisateurId);
        if (!$adresse) {
            throw ValidationException::withMessages([
                'id' => ['Adresse introuvable.'],
            ]);
        }

        return $this->adresseRepository->delete($id);
    }

    public function setDefaultExpedition(int $id, int $utilisateurId)
    {
        return DB::transaction(function () use ($id, $utilisateurId) {
            $adresse = $this->adresseRepository->findByIdForUser($id, $utilisateurId);
            if (!$adresse) {
                throw ValidationException::withMessages([
                    'id' => ['Adresse introuvable.'],
                ]);
            }

            $this->adresseRepository->clearDefaultExpeditionForUser($utilisateurId);

            return $this->adresseRepository->update($id, [
                'par_defaut_expedition' => true,
                'date_modification' => now(),
            ]);
        });
    }

    public function getDefaultExpedition(int $utilisateurId)
    {
        return $this->adresseRepository->getDefaultExpeditionByUser($utilisateurId);
    }

    /**
     * Upload d'image pour l'adresse
     */
    // public function uploadImage($file)
    // {
    //     $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
    //     $destinationPath = public_path('image-lieu');
    //     if (!file_exists($destinationPath)) {
    //         mkdir($destinationPath, 0755, true);
    //     }
        
    //     $file->move($destinationPath, $filename);
        
    //     return $filename;
    // }
        /**
     * Upload d'image pour l'adresse - Retourne l'URL complète
     */
    public function uploadImage($file)
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        $destinationPath = public_path('image-lieu');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        
        $file->move($destinationPath, $filename);
            
        // ✅ Retourner l'URL complète comme dans BoutiqueMisa

        $baseUrl = rtrim(config('app.url'), '/');

        return $baseUrl . '/image-lieu/' . $filename;
    
    }
}