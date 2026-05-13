<?php

namespace App\Services\Sales;

use App\Models\Panier;
use App\Repositories\Sales\CommandeRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommandeService
{
    public function __construct(
        private readonly CommandeRepositoryInterface $commandeRepository
    ) {
    }

    public function index(int $userId, ?string $statut = null)
    {
        if ($statut) {
            return $this->commandeRepository->allByUserAndStatus($userId, $statut);
        }

        return $this->commandeRepository->allByUser($userId);
    }

    public function adminIndex(?string $statut = null)
    {
        if ($statut) {
            return $this->commandeRepository->allByStatus($statut);
        }

        return $this->commandeRepository->all();
    }

    public function show(int $userId, string $uuid)
    {
        $commande = $this->commandeRepository->findByUuidForUser($uuid, $userId);
        if (!$commande) {
            throw ValidationException::withMessages([
                'commande_uuid' => ['Commande introuvable.'],
            ]);
        }

        return [
            'items' => $this->commandeRepository->findByUuid($uuid),
            'resume' => [
                'commande_uuid' => $uuid,
                'statut' => $commande->statut,
                'sous_total' => $commande->sous_total,
                'livraison' => $commande->livraison,
                'total' => $commande->total,
                'devise' => $commande->devise,
            ],
        ];
    }

    public function adminShow(string $uuid)
    {
        $items = $this->commandeRepository->findByUuid($uuid);
        $commande = $items->first();
        if (!$commande) {
            throw ValidationException::withMessages([
                'commande_uuid' => ['Commande introuvable.'],
            ]);
        }

        return [
            'items' => $items,
            'resume' => [
                'commande_uuid' => $uuid,
                'statut' => $commande->statut,
                'sous_total' => $commande->sous_total,
                'livraison' => $commande->livraison,
                'total' => $commande->total,
                'devise' => $commande->devise,
            ],
        ];
    }

    public function createFromPanier(int $userId, array $payload): array
    {
        $itemsPanier = Panier::where('utilisateur_id', $userId)
            ->where('statut', 'actif')
            ->with('produit')
            ->get();

        if ($itemsPanier->isEmpty()) {
            throw ValidationException::withMessages([
                'panier' => ['Le panier actif est vide.'],
            ]);
        }

        $uuid = (string) Str::uuid();
        $livraison = (float) ($payload['livraison'] ?? 0);
        $devise = $payload['devise'] ?? 'MGA';
        $sousTotal = $this->calculateSousTotal($itemsPanier->all());
        $total = $sousTotal + $livraison;

        foreach ($itemsPanier as $item) {
            $this->commandeRepository->create([
                'commande_uuid' => $uuid,
                'utilisateur_id' => $userId,
                'panier_id' => $item->id,
                'devis_id' => $payload['devis_id'] ?? null,
                'statut' => 'en_attente',
                'sous_total' => $sousTotal,
                'livraison' => $livraison,
                'total' => $total,
                'devise' => $devise,
                'adresse_expedition_id' => $payload['adresse_expedition_id'] ?? null,
                'adresse_facturation_id' => $payload['adresse_facturation_id'] ?? null,
                'produit_id' => $item->produit_id,
                'titre' => $item->titre,
                'reference' => $item->produit?->reference,
                'prix_unitaire' => $item->prix_unitaire ?? 0,
                'quantite' => $item->quantite ?? 1,
                'meta_json' => $payload['meta_json'] ?? null,
            ]);
        }

        Panier::where('utilisateur_id', $userId)
            ->where('statut', 'actif')
            ->update(['statut' => 'commande']);

        return [
            'commande_uuid' => $uuid,
            'items' => $this->commandeRepository->findByUuid($uuid),
        ];
    }

    public function updateStatus(int $userId, string $uuid, string $newStatus)
    {
        $commande = $this->commandeRepository->findByUuidForUser($uuid, $userId);
        if (!$commande) {
            throw ValidationException::withMessages([
                'commande_uuid' => ['Commande introuvable.'],
            ]);
        }

        if (!$this->canTransition($commande->statut, $newStatus)) {
            throw ValidationException::withMessages([
                'statut' => ['Transition de statut non autorisée.'],
            ]);
        }

        return $this->commandeRepository->updateByUuid($uuid, ['statut' => $newStatus]);
    }

    public function cancel(int $userId, string $uuid)
    {
        return $this->updateStatus($userId, $uuid, 'annulee');
    }

    public function adminUpdateStatus(string $uuid, string $newStatus)
    {
        $commande = $this->commandeRepository->findByUuid($uuid)->first();
        if (!$commande) {
            throw ValidationException::withMessages([
                'commande_uuid' => ['Commande introuvable.'],
            ]);
        }

        if (!$this->canTransition($commande->statut, $newStatus)) {
            throw ValidationException::withMessages([
                'statut' => ['Transition de statut non autorisee.'],
            ]);
        }

        return $this->commandeRepository->updateByUuid($uuid, ['statut' => $newStatus]);
    }

    public function adminCancel(string $uuid)
    {
        return $this->adminUpdateStatus($uuid, 'annulee');
    }

    public function calculateSousTotal(array $items): float
    {
        return (float) collect($items)->sum(function ($item) {
            return ((float) ($item->prix_unitaire ?? 0)) * ((int) ($item->quantite ?? 0));
        });
    }

    private function canTransition(string $current, string $next): bool
    {
        $transitions = [
            'en_attente' => ['payee', 'annulee', 'remboursee'],
            'payee' => ['en_traitement', 'annulee', 'remboursee'],
            'en_traitement' => ['expediee', 'annulee', 'remboursee'],
            'expediee' => ['terminee', 'remboursee'],
            'terminee' => ['remboursee'],
            'annulee' => [],
            'remboursee' => [],
        ];

        return in_array($next, $transitions[$current] ?? [], true);
    }
}
