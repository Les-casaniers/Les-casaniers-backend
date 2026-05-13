<?php

namespace App\Services\AvisClients;

use App\Repositories\AvisClients\AvisClientRepositoryInterface;
use Illuminate\Validation\ValidationException;

class AvisClientService
{
    public function __construct(
        private readonly AvisClientRepositoryInterface $avisRepository
    ) {
    }

    public function ajouterAvis(int $utilisateurId, array $payload)
    {
        $this->validateAvis($payload);

        if ($this->hasAlreadyReviewed($utilisateurId, (int) $payload['produit_id'])) {
            throw ValidationException::withMessages([
                'produit_id' => ['Vous avez déjà laissé un avis sur ce produit.'],
            ]);
        }

        return $this->avisRepository->create([
            'produit_id' => (int) $payload['produit_id'],
            'utilisateur_id' => $utilisateurId,
            'note' => (int) $payload['note'],
            'corps' => $payload['corps'] ?? null,
            'publie' => false,
        ]);
    }

    public function getAvisByProduit(int $produitId)
    {
        return $this->avisRepository->getPublishedByProduit($produitId);
    }

    public function getAvisById(int $id)
    {
        $avis = $this->avisRepository->findByIdWithRelations($id);
        if (!$avis) {
            throw ValidationException::withMessages([
                'id' => ['Avis introuvable.'],
            ]);
        }

        return $avis;
    }

    public function getMesAvis(int $utilisateurId)
    {
        return $this->avisRepository->getByUser($utilisateurId);
    }

    public function modifierAvis(int $id, array $payload, int $actorId, bool $isAdmin = false)
    {
        $avis = $this->avisRepository->findById($id);
        if (!$avis) {
            throw ValidationException::withMessages(['id' => ['Avis introuvable.']]);
        }

        if (!$isAdmin && (int) $avis->utilisateur_id !== $actorId) {
            throw ValidationException::withMessages(['authorization' => ['Action non autorisée.']]);
        }

        if (array_key_exists('note', $payload) && ((int) $payload['note'] < 1 || (int) $payload['note'] > 5)) {
            throw ValidationException::withMessages(['note' => ['La note doit être comprise entre 1 et 5.']]);
        }

        $updates = [];
        if (array_key_exists('note', $payload)) {
            $updates['note'] = (int) $payload['note'];
        }
        if (array_key_exists('corps', $payload)) {
            $updates['corps'] = $payload['corps'];
        }
        if (array_key_exists('publie', $payload)) {
            $updates['publie'] = (bool) $payload['publie'];
        } elseif (!$isAdmin) {
            $updates['publie'] = false;
        }

        return $this->avisRepository->update($id, $updates);
    }

    public function supprimerAvis(int $id, int $actorId, bool $isAdmin = false): bool
    {
        $avis = $this->avisRepository->findById($id);
        if (!$avis) {
            throw ValidationException::withMessages(['id' => ['Avis introuvable.']]);
        }

        if (!$isAdmin && (int) $avis->utilisateur_id !== $actorId) {
            throw ValidationException::withMessages(['authorization' => ['Action non autorisée.']]);
        }

        return $this->avisRepository->delete($id);
    }

    public function publierAvis(int $id)
    {
        return $this->avisRepository->update($id, ['publie' => true]);
    }

    public function refuserAvis(int $id)
    {
        return $this->avisRepository->update($id, ['publie' => false]);
    }

    public function calculerMoyenneProduit(int $produitId): float
    {
        return round($this->avisRepository->getAverageNoteByProduit($produitId), 1);
    }

    public function getStatistiquesProduit(int $produitId): array
    {
        $stats = $this->avisRepository->getStatsByProduit($produitId);
        $stats['moyenne'] = $this->calculerMoyenneProduit($produitId);

        return $stats;
    }

    public function hasAlreadyReviewed(int $utilisateurId, int $produitId): bool
    {
        return $this->avisRepository->existsByProduitAndUser($produitId, $utilisateurId);
    }

    public function getAvisAdmin(array $filters = [])
    {
        return $this->avisRepository->getAdminList($filters);
    }

    public function searchAvis(array $filters = [], bool $isAdmin = false)
    {
        if (!$isAdmin) {
            $filters['publie'] = true;
        }

        return $this->avisRepository->search($filters);
    }

    public function getLatestAvis(int $limit = 10)
    {
        return $this->avisRepository->getLatest($limit);
    }

    public function validateAvis(array $payload): void
    {
        if (empty($payload['produit_id'])) {
            throw ValidationException::withMessages([
                'produit_id' => ['Le produit est obligatoire.'],
            ]);
        }

        if (!isset($payload['note']) || (int) $payload['note'] < 1 || (int) $payload['note'] > 5) {
            throw ValidationException::withMessages([
                'note' => ['La note doit être comprise entre 1 et 5.'],
            ]);
        }
    }
}
