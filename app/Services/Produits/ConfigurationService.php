<?php

namespace App\Services\Produits;

use App\Repositories\Configurations\ConfigurationRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ConfigurationService
{
    private const NOM_CONFIGURATIONS = [
        'cpu',
        'carte_mere',
        'gpu',
        'ram',
        'ssd',
        'hdd',
        'stockage',
        'alimentation',
        'boitier',
        'refroidissement',
        'ventilateur',
        'ecran',
        'clavier',
        'souris',
        'os',
        'reseau',
        'autre',
    ];

    public function __construct(
        private readonly ConfigurationRepositoryInterface $configurationRepository
    ) {
    }

    public function index(array $filters = [])
    {
        return $this->configurationRepository->getAll($filters);
    }

    public function store(array $payload)
    {
        $rows = $this->normalizeRows($payload);

        return count($rows) === 1
            ? $this->configurationRepository->create($rows[0])
            : $this->configurationRepository->createMany($rows);
    }

    public function update(int $configurationId, array $payload)
    {
        $configuration = $this->configurationRepository->findById($configurationId);
        if (!$configuration) {
            throw ValidationException::withMessages([
                'configuration_id' => ['Configuration introuvable.'],
            ]);
        }

        $row = $this->normalizeRow(array_merge($configuration->toArray(), $payload));

        unset($row['produit_id']);

        return $this->configurationRepository->update($configurationId, $row);
    }

    public function destroy(int $configurationId): bool
    {
        return $this->configurationRepository->delete($configurationId) > 0;
    }

    private function normalizeRows(array $payload): array
    {
        $produitId = $payload['produit_id'] ?? null;
        $configs = $payload['configurations'] ?? null;

        if (is_array($configs)) {
            return collect($configs)
                ->map(fn (array $row) => $this->normalizeRow(array_merge($row, ['produit_id' => $produitId])))
                ->values()
                ->all();
        }

        return [$this->normalizeRow($payload)];
    }

    private function normalizeRow(array $row): array
    {
        $nomConfiguration = $row['nom_configuration'] ?? null;

        if (!in_array($nomConfiguration, self::NOM_CONFIGURATIONS, true)) {
            throw ValidationException::withMessages([
                'nom_configuration' => ['Le nom de configuration selectionne est invalide.'],
            ]);
        }

        return [
            'produit_id' => (int) $row['produit_id'],
            'nom_configuration' => $nomConfiguration,
            'type' => $this->nullableString($row['type'] ?? null),
            'detail' => $this->nullableString($row['detail'] ?? null),
            'capacite' => $this->nullableString($row['capacite'] ?? null),
            'prix_total' => $this->nullableNumber($row['prix_total'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
