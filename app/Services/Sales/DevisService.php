<?php

namespace App\Services\Sales;

use App\Enums\Sales\DevisStatut;
use App\Models\Devis;
use App\Repositories\Paniers\PanierRepositoryInterface;
use App\Repositories\Sales\DevisRepositoryInterface;
use App\Traits\CalculatesLineItems;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DevisService
{
    use CalculatesLineItems;

    public function __construct(
        private readonly DevisRepositoryInterface $devisRepository,
        private readonly CommandeService $commandeService,
        private readonly PanierRepositoryInterface $panierRepository
    ) {
    }

    // ──────────────────────────────────────────────
    //  Lecture
    // ──────────────────────────────────────────────

    /**
     * Liste des devis d'un utilisateur, avec filtre optionnel par statut.
     */
    public function index(int $userId, ?string $statut = null): Collection
    {
        if ($statut) {
            return $this->devisRepository->allByUserAndStatus($userId, $statut);
        }

        return $this->devisRepository->allByUser($userId);
    }

    /**
     * Liste admin de tous les devis, avec filtre optionnel par statut.
     */
    public function adminIndex(?string $statut = null): Collection
    {
        if ($statut) {
            return $this->devisRepository->allByStatus($statut);
        }

        return $this->devisRepository->all();
    }

    /**
     * Détail d'un devis pour un utilisateur.
     */
    public function show(int $userId, int $devisId): Devis
    {
        return $this->findOrFail($devisId, $userId);
    }

    /**
     * Détail d'un devis pour l'admin (sans contrainte utilisateur).
     */
    public function adminShow(int $devisId): Devis
    {
        $devis = $this->devisRepository->findByIdForUser($devisId, 0);

        // Fallback: chercher sans contrainte utilisateur
        $devis = $devis ?? Devis::with(['utilisateur', 'panier'])->findOrFail($devisId);

        return $devis;
    }

    // ──────────────────────────────────────────────
    //  Écriture
    // ──────────────────────────────────────────────

    /**
     * Crée un nouveau devis à partir du panier actif de l'utilisateur.
     *
     * Si un `panier_id` spécifique est fourni, le devis est basé sur cet item uniquement.
     * Sinon, le montant est calculé à partir de l'ensemble du panier actif.
     */
    public function create(int $userId, array $payload): Devis
    {
        $montantTotal = 0.0;

        if (!empty($payload['panier_id'])) {
            $panier = $this->panierRepository->findById((int) $payload['panier_id'], $userId);

            if (!$panier) {
                throw ValidationException::withMessages([
                    'panier_id' => ['Panier introuvable pour cet utilisateur.'],
                ]);
            }

            $montantTotal = $this->calculerLigne($panier);
        } else {
            $items = $this->panierRepository->getByUser($userId, 'actif');
            $montantTotal = $this->calculerSousTotal($items);
        }

        return $this->devisRepository->create([
            'utilisateur_id' => $userId,
            'panier_id' => $payload['panier_id'] ?? null,
            'statut' => DevisStatut::Brouillon->value,
            'note' => $payload['note'] ?? null,
            'montant_total' => $montantTotal,
            'devise' => $payload['devise'] ?? 'MGA',
        ]);
    }

    /**
     * Met à jour un devis brouillon.
     */
    public function update(int $userId, int $devisId, array $payload): Devis
    {
        $devis = $this->findOrFail($devisId, $userId);
        $this->assertModifiable($devis);

        $updates = [];

        if (array_key_exists('note', $payload)) {
            $updates['note'] = $payload['note'];
        }

        if (array_key_exists('panier_id', $payload)) {
            $panier = $this->panierRepository->findById((int) $payload['panier_id'], $userId);

            if (!$panier) {
                throw ValidationException::withMessages([
                    'panier_id' => ['Panier introuvable pour cet utilisateur.'],
                ]);
            }

            $updates['panier_id'] = $panier->id;
            $updates['montant_total'] = $this->calculerLigne($panier);
        }

        return $this->devisRepository->update($devisId, $updates);
    }

    // ──────────────────────────────────────────────
    //  Transitions de statut
    // ──────────────────────────────────────────────

    /**
     * Envoie un devis brouillon au client.
     */
    public function send(int $userId, int $devisId): Devis
    {
        return $this->transitionStatut($userId, $devisId, DevisStatut::Envoye);
    }

    /**
     * Accepte un devis envoyé et crée automatiquement la commande associée.
     *
     * Enveloppé dans une transaction pour garantir l'atomicité devis + commande.
     */
    public function accept(int $userId, int $devisId): array
    {
        return DB::transaction(function () use ($userId, $devisId) {
            $devis = $this->transitionStatut($userId, $devisId, DevisStatut::Accepte);

            $commande = $this->commandeService->createFromPanier($userId, [
                'devis_id' => $devis->id,
                'devise' => $devis->devise,
                'livraison' => 0,
            ]);

            return [
                'devis' => $devis,
                'commande' => $commande,
            ];
        });
    }

    /**
     * Refuse un devis envoyé, avec note optionnelle.
     */
    public function refuse(int $userId, int $devisId, ?string $note = null): Devis
    {
        return $this->transitionStatut($userId, $devisId, DevisStatut::Refuse, $note);
    }

    /**
     * Marque un devis comme expiré.
     */
    public function expire(int $userId, int $devisId): Devis
    {
        return $this->transitionStatut($userId, $devisId, DevisStatut::Expire);
    }

    // ──────────────────────────────────────────────
    //  Suppression
    // ──────────────────────────────────────────────

    /**
     * Supprime un devis brouillon.
     */
    public function delete(int $userId, int $devisId): bool
    {
        $devis = $this->findOrFail($devisId, $userId);

        if (!$devis->statut->estSupprimable()) {
            throw ValidationException::withMessages([
                'statut' => ['Seuls les devis en brouillon peuvent être supprimés.'],
            ]);
        }

        return $this->devisRepository->delete($devisId) > 0;
    }

    // ──────────────────────────────────────────────
    //  Méthodes privées
    // ──────────────────────────────────────────────

    /**
     * Récupère un devis ou lève une exception.
     */
    private function findOrFail(int $devisId, int $userId): Devis
    {
        $devis = $this->devisRepository->findByIdForUser($devisId, $userId);

        if (!$devis) {
            throw ValidationException::withMessages([
                'devis_id' => ['Devis introuvable.'],
            ]);
        }

        return $devis;
    }

    /**
     * Vérifie qu'un devis est en brouillon (modifiable).
     */
    private function assertModifiable(Devis $devis): void
    {
        if (!$devis->statut->estModifiable()) {
            throw ValidationException::withMessages([
                'statut' => ['Seuls les devis en brouillon peuvent être modifiés.'],
            ]);
        }
    }

    /**
     * Effectue une transition de statut en validant la machine à états.
     */
    private function transitionStatut(
        int $userId,
        int $devisId,
        DevisStatut $cible,
        ?string $note = null
    ): Devis {
        $devis = $this->findOrFail($devisId, $userId);

        if (!$devis->statut->peutTransitionVers($cible)) {
            throw ValidationException::withMessages([
                'statut' => ["Transition de statut non autorisée ({$devis->statut->value} → {$cible->value})."],
            ]);
        }

        $updates = ['statut' => $cible->value];

        if (!is_null($note)) {
            $updates['note'] = $note;
        }

        return $this->devisRepository->update($devisId, $updates);
    }
}