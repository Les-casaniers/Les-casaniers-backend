<?php

namespace App\Repositories\Configurations;

use App\Models\Configuration;
use Illuminate\Support\Facades\Log;  // ← IMPORTANT !

class ConfigurationRepository implements ConfigurationRepositoryInterface
{
    public function getAllByUser(?int $userId)
    {
        return Configuration::with(['produit'])
            ->where('utilisateur_id', $userId)
            ->latest()
            ->get();
    }

    public function findById(int $id)
    {
        return Configuration::findOrFail($id);
    }

    public function findByIdForUser(int $id, ?int $userId)
    {
        return Configuration::where('id', $id)
            ->where('utilisateur_id', $userId)
            ->first();
    }

    public function create(array $data)
    {
        // Log pour déboguer
        Log::info('Repository create - données reçues:', $data);
        
        // Traiter les composants AVANT l'insertion
        if (isset($data['composants_json']) && is_array($data['composants_json'])) {
            $composantsTraites = [];
            foreach ($data['composants_json'] as $composant) {
                $composantsTraites[] = [
                    'nom' => $composant['nom'] ?? null,
                    'prix' => (float) ($composant['prix'] ?? 0),
                    'quantite' => (int) ($composant['quantite'] ?? 1),
                ];
            }
            
            $data['composants_json'] = json_encode($composantsTraites, JSON_UNESCAPED_UNICODE);
            
            Log::info('composants_json après traitement:', [
                'encoded' => $data['composants_json']
            ]);
        }
        
        // Log avant insertion
        Log::info('Données finales avant Configuration::create:', $data);
        
        $result = Configuration::create($data);
        
        Log::info('Configuration créée avec succès, ID: ' . $result->id);
        
        return $result;
    }

    public function update(int $id, array $data)
    {
        if (isset($data['composants_json']) && is_array($data['composants_json'])) {
            $composantsTraites = [];
            foreach ($data['composants_json'] as $composant) {
                $composantsTraites[] = [
                    'nom' => $composant['nom'] ?? null,
                    'prix' => (float) ($composant['prix'] ?? 0),
                    'quantite' => (int) ($composant['quantite'] ?? 1),
                ];
            }
            $data['composants_json'] = json_encode($composantsTraites, JSON_UNESCAPED_UNICODE);
        }
        
        $config = Configuration::findOrFail($id);
        $config->update($data);
        return $config;
    }

    public function delete(int $id)
    {
        return Configuration::destroy($id);
    }
}