<?php

namespace App\Services\Produits;

use App\Repositories\Configurations\ConfigurationRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ConfigurationService
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $configurationRepository
    ) {
    }

    public function index(int $utilisateurId)
    {
        return $this->configurationRepository->getAllByUser($utilisateurId);
    }

    public function store(int $utilisateurId, array $payload)
    {
        $this->validateNomConfiguration($payload);

        // 🔥 CORRECTION IMPORTANTE: Conserver le champ "nom" des composants
        $composants = $payload['composants_json'] ?? [];
        
        // Vérifier que chaque composant a bien tous ses champs (nom, prix, quantite)
        $composantsTraites = [];
        foreach ($composants as $composant) {
            $composantsTraites[] = [
                'nom' => $composant['nom'] ?? null,           // ← Conserver le nom !
                'prix' => (float) ($composant['prix'] ?? 0),
                'quantite' => (int) ($composant['quantite'] ?? 1),
            ];
        }
        
        $payload['utilisateur_id'] = $utilisateurId;
        $payload['prix_total'] = $this->calculatePrixTotal($composantsTraites);
        $payload['composants_json'] = $composantsTraites; // ← Remplacer par les composants traités

        return $this->configurationRepository->create($payload);
    }

    public function update(int $utilisateurId, int $configurationId, array $payload)
    {
        $configuration = $this->configurationRepository->findByIdForUser($configurationId, $utilisateurId);
        if (!$configuration) {
            throw ValidationException::withMessages([
                'configuration_id' => ['Configuration introuvable pour cet utilisateur.'],
            ]);
        }

        $next = array_merge($configuration->toArray(), $payload);
        $this->validateNomConfiguration($next);

        if (array_key_exists('composants_json', $payload)) {
            // 🔥 CORRECTION: Conserver le champ "nom" lors de la mise à jour aussi
            $composants = $payload['composants_json'];
            $composantsTraites = [];
            foreach ($composants as $composant) {
                $composantsTraites[] = [
                    'nom' => $composant['nom'] ?? null,
                    'prix' => (float) ($composant['prix'] ?? 0),
                    'quantite' => (int) ($composant['quantite'] ?? 1),
                ];
            }
            $payload['prix_total'] = $this->calculatePrixTotal($composantsTraites);
            $payload['composants_json'] = $composantsTraites;
        }

        return $this->configurationRepository->update($configurationId, $payload);
    }

    public function destroy(int $utilisateurId, int $configurationId): bool
    {
        $configuration = $this->configurationRepository->findByIdForUser($configurationId, $utilisateurId);
        if (!$configuration) {
            throw ValidationException::withMessages([
                'configuration_id' => ['Configuration introuvable pour cet utilisateur.'],
            ]);
        }

        return $this->configurationRepository->delete($configurationId) > 0;
    }

    private function validateNomConfiguration(array $payload): void
    {
        $nom = $payload['nom_configuration'] ?? null;
        $autre = $payload['nom_configuration_autre'] ?? null;

        if ($nom === 'autre' && blank($autre)) {
            throw ValidationException::withMessages([
                'nom_configuration_autre' => [
                    'Le champ nom_configuration_autre est obligatoire si nom_configuration = autre.',
                ],
            ]);
        }

        if ($nom !== 'autre' && !blank($autre)) {
            throw ValidationException::withMessages([
                'nom_configuration_autre' => [
                    'Le champ nom_configuration_autre doit être null pour une configuration différente de autre.',
                ],
            ]);
        }
    }

    private function calculatePrixTotal(array $composants): float
    {
        return (float) collect($composants)->sum(function ($composant) {
            if (!is_array($composant)) {
                return 0;
            }

            $prix = (float) ($composant['prix'] ?? 0);
            $quantite = (int) ($composant['quantite'] ?? 1);

            return $prix * max($quantite, 1);
        });
    }
}