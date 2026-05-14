<?php

namespace App\Services\Sales;

use App\Enums\Sales\CommandeStatut;
use App\Repositories\Paniers\PanierRepositoryInterface;
use App\Repositories\Sales\CommandeRepositoryInterface;
use App\Services\AdminNotificationService;
use App\Services\Produits\ProduitService;
use App\Traits\CalculatesLineItems;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommandeService
{
    use CalculatesLineItems;

    /**
     * Statuts pour lesquels le stock a été décrémenté.
     * Si on transite VERS annulee/remboursee depuis l'un de ces statuts,
     * le stock doit être restauré.
     */
    private const STOCK_DECREMENTED_STATUSES = [
        CommandeStatut::Payee,
        CommandeStatut::EnTraitement,
        CommandeStatut::Expediee,
        CommandeStatut::Terminee,
    ];

    public function __construct(
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly PanierRepositoryInterface $panierRepository,
        private readonly ProduitService $produitService,
        private readonly AdminNotificationService $notificationService,
    ) {
    }

    public function index(int $userId, ?string $statut = null): Collection
    {
        if ($statut) {
            return $this->commandeRepository->allByUserAndStatus($userId, $statut);
        }
        return $this->commandeRepository->allByUser($userId);
    }

    public function show(int $userId, string $uuid): array
    {
        $commande = $this->findOrFailForUser($uuid, $userId);
        return $this->formatDetail($uuid, $commande);
    }

    public function adminIndex(?string $statut = null): Collection
    {
        if ($statut) {
            return $this->commandeRepository->allByStatus($statut);
        }
        return $this->commandeRepository->all();
    }

    public function adminShow(string $uuid): array
    {
        $items = $this->commandeRepository->findByUuid($uuid);
        $commande = $items->first();
        if (!$commande) {
            throw ValidationException::withMessages([
                'commande_uuid' => ['Commande introuvable.'],
            ]);
        }
        return $this->formatDetail($uuid, $commande, $items);
    }

    /**
     * Crée une commande à partir du panier actif.
     * Atomique : validation stock + création items + marquage panier.
     */
    public function createFromPanier(int $userId, array $payload): array
    {
        return DB::transaction(function () use ($userId, $payload) {
            $itemsPanier = $this->panierRepository->getActiveItemsWithProduit($userId);

            if ($itemsPanier->isEmpty()) {
                throw ValidationException::withMessages([
                    'panier' => ['Le panier actif est vide.'],
                ]);
            }

            // Vérifier la disponibilité du stock AVANT de créer la commande
            foreach ($itemsPanier as $item) {
                if ($item->produit_id) {
                    $produit = $this->produitService->getProduitById($item->produit_id);
                    if ($produit && (!$produit->actif || !$produit->est_dispo || (int) $produit->quantite_stock <= 0)) {
                        throw ValidationException::withMessages([
                            'stock' => [
                                "Le produit \"{$produit->nom}\" est indisponible."
                            ],
                        ]);
                    }
                    if ($produit && $produit->quantite_stock < ($item->quantite ?? 1)) {
                        throw ValidationException::withMessages([
                            'stock' => [
                                "Stock insuffisant pour \"{$produit->nom}\" " .
                                "(demandé: {$item->quantite}, disponible: {$produit->quantite_stock})."
                            ],
                        ]);
                    }
                }
            }

            $uuid = (string) Str::uuid();
            $livraison = (float) ($payload['livraison'] ?? 0);
            $devise = $payload['devise'] ?? 'MGA';
            $sousTotal = $this->calculerSousTotal($itemsPanier);
            $total = $sousTotal + $livraison;

            foreach ($itemsPanier as $item) {
                $this->commandeRepository->create([
                    'commande_uuid' => $uuid,
                    'utilisateur_id' => $userId,
                    'panier_id' => $item->id,
                    'devis_id' => $payload['devis_id'] ?? null,
                    'statut' => CommandeStatut::EnAttente->value,
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

            $this->panierRepository->markActiveAsCommande($userId);

            // Notifier l'admin de la nouvelle commande
            try {
                $items = $this->commandeRepository->findByUuid($uuid);
                $commande = $items->first();
                $clientNom = trim(($commande->utilisateur->prenom ?? '') . ' ' . ($commande->utilisateur->nom ?? ''));
                $this->notificationService->notifyNewCommande(
                    $uuid,
                    $total,
                    $devise,
                    $clientNom ?: 'Client'
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Notification new commande failed', ['error' => $e->getMessage()]);
            }

            return [
                'commande_uuid' => $uuid,
                'items' => $this->commandeRepository->findByUuid($uuid),
            ];
        });
    }

    public function updateStatus(int $userId, string $uuid, string $newStatus): Collection
    {
        $commande = $this->findOrFailForUser($uuid, $userId);
        $cible = $this->resolveStatut($newStatus);
        $this->assertTransition($commande->statut, $cible);

        return $this->applyStatusTransition($uuid, $commande->statut, $cible);
    }

    public function cancel(int $userId, string $uuid): Collection
    {
        return $this->updateStatus($userId, $uuid, CommandeStatut::Annulee->value);
    }

    public function adminUpdateStatus(string $uuid, string $newStatus): Collection
    {
        $items = $this->commandeRepository->findByUuid($uuid);
        $commande = $items->first();
        if (!$commande) {
            throw ValidationException::withMessages([
                'commande_uuid' => ['Commande introuvable.'],
            ]);
        }
        $cible = $this->resolveStatut($newStatus);
        $this->assertTransition($commande->statut, $cible);

        return $this->applyStatusTransition($uuid, $commande->statut, $cible);
    }

    public function adminCancel(string $uuid): Collection
    {
        return $this->adminUpdateStatus($uuid, CommandeStatut::Annulee->value);
    }

    // ─────────────────────────────────────────────────────────
    //  Stock Management — Logique centralisée
    // ─────────────────────────────────────────────────────────

    /**
     * Applique une transition de statut avec gestion automatique du stock.
     *
     * - en_attente → payee : décrémente le stock de chaque produit
     * - (payee|en_traitement|expediee|terminee) → annulee : restaure le stock
     * - (payee|en_traitement|expediee|terminee) → remboursee : restaure le stock
     */
    private function applyStatusTransition(string $uuid, CommandeStatut $current, CommandeStatut $cible): Collection
    {
        $result = DB::transaction(function () use ($uuid, $current, $cible) {
            $items = $this->commandeRepository->findByUuidWithLock($uuid);

            // Décrémentation du stock : uniquement lors du passage à "payée"
            if ($cible === CommandeStatut::Payee && $current === CommandeStatut::EnAttente) {
                $this->decrementStockForItems($items);
            }

            // Restauration du stock : annulation ou remboursement depuis un état où le stock a été décrémenté
            if ($this->isStockRestoreTransition($current, $cible)) {
                $this->restoreStockForItems($items);
            }

            return $this->commandeRepository->updateByUuid($uuid, ['statut' => $cible->value]);
        });

        // Notifier l'admin du changement de statut (hors transaction pour ne pas bloquer)
        try {
            $this->notificationService->notifyCommandeStatusChange(
                $uuid,
                $current->value,
                $cible->value
            );

            // Vérifier les stocks bas après décrémentation
            if ($cible === CommandeStatut::Payee && $current === CommandeStatut::EnAttente) {
                $this->checkLowStockAfterDecrement($uuid);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification commande status failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Décrémente le stock pour chaque item de la commande.
     */
    private function decrementStockForItems(Collection $items): void
    {
        foreach ($items as $item) {
            if ($item->produit_id && $item->quantite > 0) {
                $this->produitService->decrementStock($item->produit_id, (int) $item->quantite);
            }
        }
    }

    /**
     * Restaure le stock pour chaque item de la commande.
     */
    private function restoreStockForItems(Collection $items): void
    {
        foreach ($items as $item) {
            if ($item->produit_id && $item->quantite > 0) {
                $this->produitService->restoreStock($item->produit_id, (int) $item->quantite);
            }
        }
    }

    /**
     * Vérifie si la transition nécessite une restauration du stock.
     */
    private function isStockRestoreTransition(CommandeStatut $current, CommandeStatut $cible): bool
    {
        $isFromStockDecrementedState = in_array($current, self::STOCK_DECREMENTED_STATUSES, true);
        $isToRestorationState = $cible === CommandeStatut::Annulee || $cible === CommandeStatut::Remboursee;

        return $isFromStockDecrementedState && $isToRestorationState;
    }

    // ─────────────────────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────────────────────

    private function findOrFailForUser(string $uuid, int $userId): object
    {
        $commande = $this->commandeRepository->findByUuidForUser($uuid, $userId);
        if (!$commande) {
            throw ValidationException::withMessages([
                'commande_uuid' => ['Commande introuvable.'],
            ]);
        }
        return $commande;
    }

    private function resolveStatut(string $value): CommandeStatut
    {
        $statut = CommandeStatut::tryFrom($value);
        if (!$statut) {
            throw ValidationException::withMessages([
                'statut' => ["Statut invalide : {$value}."],
            ]);
        }
        return $statut;
    }

    private function assertTransition(CommandeStatut $current, CommandeStatut $cible): void
    {
        if (!$current->peutTransitionVers($cible)) {
            throw ValidationException::withMessages([
                'statut' => ["Transition non autorisée ({$current->value} → {$cible->value})."],
            ]);
        }
    }

    private function formatDetail(string $uuid, object $commande, ?Collection $items = null): array
    {
        return [
            'items' => $items ?? $this->commandeRepository->findByUuid($uuid),
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

    /**
     * Vérifie les niveaux de stock après décrémentation et notifie
     * l'admin si un produit est en stock faible (≤ 5) ou en rupture.
     */
    private function checkLowStockAfterDecrement(string $uuid): void
    {
        $items = $this->commandeRepository->findByUuid($uuid);

        foreach ($items as $item) {
            if (!$item->produit_id) {
                continue;
            }

            $produit = $this->produitService->getProduitById($item->produit_id);

            if (!$produit) {
                continue;
            }

            $seuil = 5; // Seuil d'alerte stock faible

            if ($produit->quantite_stock <= $seuil) {
                $this->notificationService->notifyLowStock(
                    $produit->nom,
                    (int) $produit->quantite_stock,
                    $produit->id
                );
            }
        }
    }
}
