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

            $payload['date_modification'] = now();

            return $this->adresseRepository->update($id, $payload);
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
}
