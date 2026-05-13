<?php

namespace App\Services\Sales;

use App\Models\Panier;
use App\Repositories\Sales\DevisRepositoryInterface;
use Illuminate\Validation\ValidationException;

class DevisService
{
    public function __construct(
        private readonly DevisRepositoryInterface $devisRepository,
        private readonly CommandeService $commandeService
    ) {
    }

    public function index(int $userId, ?string $statut = null)
    {
        if ($statut) {
            return $this->devisRepository->allByUserAndStatus($userId, $statut);
        }

        return $this->devisRepository->allByUser($userId);
    }

    public function show(int $userId, int $devisId)
    {
        $devis = $this->devisRepository->findByIdForUser($devisId, $userId);
        if (!$devis) {
            throw ValidationException::withMessages([
                'devis_id' => ['Devis introuvable.'],
            ]);
        }

        return $devis;
    }

    public function create(int $userId, array $payload)
    {
        $panier = null;
        $montantTotal = 0;

        if (!empty($payload['panier_id'])) {
            $panier = Panier::where('id', $payload['panier_id'])
                ->where('utilisateur_id', $userId)
                ->first();

            if (!$panier) {
                throw ValidationException::withMessages([
                    'panier_id' => ['Panier introuvable pour cet utilisateur.'],
                ]);
            }

            $montantTotal = ((float) ($panier->prix_unitaire ?? 0)) * ((int) ($panier->quantite ?? 0));
        } else {
            $items = Panier::where('utilisateur_id', $userId)
                ->where('statut', 'actif')
                ->get();

            $montantTotal = (float) $items->sum(function ($item) {
                return ((float) ($item->prix_unitaire ?? 0)) * ((int) ($item->quantite ?? 0));
            });
        }

        return $this->devisRepository->create([
            'utilisateur_id' => $userId,
            'panier_id' => $payload['panier_id'] ?? null,
            'statut' => $payload['statut'] ?? 'brouillon',
            'note' => $payload['note'] ?? null,
            'montant_total' => $montantTotal,
            'devise' => $payload['devise'] ?? 'MGA',
        ]);
    }

    public function update(int $userId, int $devisId, array $payload)
    {
        $devis = $this->devisRepository->findByIdForUser($devisId, $userId);
        if (!$devis) {
            throw ValidationException::withMessages([
                'devis_id' => ['Devis introuvable.'],
            ]);
        }

        if ($devis->statut !== 'brouillon') {
            throw ValidationException::withMessages([
                'statut' => ['Seuls les devis brouillon peuvent être modifiés.'],
            ]);
        }

        $updates = [];
        if (array_key_exists('note', $payload)) {
            $updates['note'] = $payload['note'];
        }
        if (array_key_exists('panier_id', $payload)) {
            $panier = Panier::where('id', $payload['panier_id'])
                ->where('utilisateur_id', $userId)
                ->first();
            if (!$panier) {
                throw ValidationException::withMessages([
                    'panier_id' => ['Panier introuvable pour cet utilisateur.'],
                ]);
            }

            $updates['panier_id'] = $panier->id;
            $updates['montant_total'] = ((float) ($panier->prix_unitaire ?? 0)) * ((int) ($panier->quantite ?? 0));
        }

        return $this->devisRepository->update($devisId, $updates);
    }

    public function send(int $userId, int $devisId)
    {
        return $this->updateStatus($userId, $devisId, 'envoye');
    }

    public function accept(int $userId, int $devisId): array
    {
        $devis = $this->updateStatus($userId, $devisId, 'accepte');

        $commande = $this->commandeService->createFromPanier($userId, [
            'devis_id' => $devis->id,
            'devise' => $devis->devise,
            'livraison' => 0,
        ]);

        return [
            'devis' => $devis,
            'commande' => $commande,
        ];
    }

    public function refuse(int $userId, int $devisId, ?string $note = null)
    {
        return $this->updateStatus($userId, $devisId, 'refuse', $note);
    }

    public function expire(int $userId, int $devisId)
    {
        return $this->updateStatus($userId, $devisId, 'expire');
    }

    public function delete(int $userId, int $devisId): bool
    {
        $devis = $this->devisRepository->findByIdForUser($devisId, $userId);
        if (!$devis) {
            throw ValidationException::withMessages([
                'devis_id' => ['Devis introuvable.'],
            ]);
        }

        if ($devis->statut !== 'brouillon') {
            throw ValidationException::withMessages([
                'statut' => ['Seuls les devis brouillon peuvent être supprimés.'],
            ]);
        }

        return $this->devisRepository->delete($devisId) > 0;
    }

    private function updateStatus(int $userId, int $devisId, string $status, ?string $note = null)
    {
        $devis = $this->devisRepository->findByIdForUser($devisId, $userId);
        if (!$devis) {
            throw ValidationException::withMessages([
                'devis_id' => ['Devis introuvable.'],
            ]);
        }

        $allowed = [
            'brouillon' => ['envoye', 'expire'],
            'envoye' => ['accepte', 'refuse', 'expire'],
            'accepte' => [],
            'refuse' => [],
            'expire' => [],
        ];

        if (!in_array($status, $allowed[$devis->statut] ?? [], true)) {
            throw ValidationException::withMessages([
                'statut' => ['Transition de statut non autorisée.'],
            ]);
        }

        $updates = ['statut' => $status];
        if (!is_null($note)) {
            $updates['note'] = $note;
        }

        return $this->devisRepository->update($devisId, $updates);
    }
}
