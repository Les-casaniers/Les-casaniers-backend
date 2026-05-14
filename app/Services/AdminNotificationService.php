<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Repositories\AdminNotification\AdminNotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    public function __construct(
        private readonly AdminNotificationRepositoryInterface $repository
    ) {
    }

    // ──────────────────────────────────────────────────────────────
    //  Lecture
    // ──────────────────────────────────────────────────────────────

    public function getAll(?string $filtre = null, ?string $type = null, int $limit = 50): Collection
    {
        return $this->repository->all($filtre, $type, $limit);
    }

    public function getById(int $id): ?AdminNotification
    {
        return $this->repository->find($id);
    }

    public function countUnread(): int
    {
        return $this->repository->countUnread();
    }

    // ──────────────────────────────────────────────────────────────
    //  Marquage
    // ──────────────────────────────────────────────────────────────

    public function markAsRead(int $id): bool
    {
        return $this->repository->markAsRead($id);
    }

    public function markAllAsRead(): int
    {
        return $this->repository->markAllAsRead();
    }

    // ──────────────────────────────────────────────────────────────
    //  Suppression
    // ──────────────────────────────────────────────────────────────

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function deleteAll(): int
    {
        return $this->repository->deleteAll();
    }

    // ──────────────────────────────────────────────────────────────
    //  Création & Dispatch (méthode centrale)
    // ──────────────────────────────────────────────────────────────

    /**
     * Crée une notification admin et la diffuse en temps réel via le fichier
     * de queue WebSocket (fichier temporaire lu par le serveur WS).
     */
    public function dispatch(string $type, string $titre, string $message, ?string $lien = null, ?string $expediteur = null, ?array $meta = null): AdminNotification
    {
        $notification = $this->repository->create([
            'type'          => $type,
            'titre'         => $titre,
            'message'       => $message,
            'lien'          => $lien,
            'expediteur'    => $expediteur,
            'meta'          => $meta,
            'lue'           => false,
            'date_creation' => now(),
        ]);

        // Écrire dans le fichier de broadcast pour le serveur WebSocket
        $this->broadcastToWebSocket($notification);

        return $notification;
    }

    // ──────────────────────────────────────────────────────────────
    //  Raccourcis métier pour chaque type d'événement
    // ──────────────────────────────────────────────────────────────

    public function notifyNewUser(string $prenom, string $nom, string $email): AdminNotification
    {
        return $this->dispatch(
            'client',
            'Nouveau client inscrit',
            "Bienvenue à {$prenom} {$nom} ({$email})",
            '/admin/clients',
            $email,
            ['email' => $email]
        );
    }

    public function notifyNewCommande(string $uuid, float $total, string $devise, string $clientNom): AdminNotification
    {
        $montant = number_format($total, 0, ',', ' ') . ' ' . $devise;

        return $this->dispatch(
            'commande',
            "Nouvelle commande #{$uuid}",
            "{$clientNom} a passé une commande de {$montant}",
            '/admin/commandes',
            null,
            ['commande_uuid' => $uuid, 'total' => $total, 'devise' => $devise]
        );
    }

    public function notifyCommandeStatusChange(string $uuid, string $oldStatus, string $newStatus): AdminNotification
    {
        $labels = [
            'en_attente'    => 'En attente',
            'payee'         => 'Payée',
            'en_traitement' => 'En traitement',
            'expediee'      => 'Expédiée',
            'terminee'      => 'Terminée',
            'annulee'       => 'Annulée',
            'remboursee'    => 'Remboursée',
        ];

        $oldLabel = $labels[$oldStatus] ?? $oldStatus;
        $newLabel = $labels[$newStatus] ?? $newStatus;

        // Déterminer le type de notification selon le nouveau statut
        $type = match ($newStatus) {
            'payee'      => 'paiement',
            'expediee'   => 'livraison',
            'annulee'    => 'alerte',
            'remboursee' => 'alerte',
            default      => 'commande',
        };

        return $this->dispatch(
            $type,
            "Commande #{$uuid} — {$newLabel}",
            "La commande est passée de \"{$oldLabel}\" à \"{$newLabel}\"",
            '/admin/commandes',
            null,
            ['commande_uuid' => $uuid, 'old_status' => $oldStatus, 'new_status' => $newStatus]
        );
    }

    public function notifyLowStock(string $produitNom, int $stockRestant, int $produitId): AdminNotification
    {
        $type = $stockRestant === 0 ? 'alerte' : 'produit';
        $titre = $stockRestant === 0
            ? "Rupture de stock — {$produitNom}"
            : "Stock faible — {$produitNom}";

        return $this->dispatch(
            $type,
            $titre,
            "Plus que {$stockRestant} unité(s) du produit \"{$produitNom}\"",
            '/admin/produits',
            null,
            ['produit_id' => $produitId, 'stock' => $stockRestant]
        );
    }

    public function notifyPaiementRecu(string $factureRef, float $montant, string $devise): AdminNotification
    {
        $montantFormate = number_format($montant, 0, ',', ' ') . ' ' . $devise;

        return $this->dispatch(
            'paiement',
            'Paiement reçu',
            "Paiement confirmé pour la facture #{$factureRef} — {$montantFormate}",
            '/admin/factures',
            null,
            ['facture_ref' => $factureRef, 'montant' => $montant, 'devise' => $devise]
        );
    }

    public function notifyFactureCreated(string $factureRef, string $commandeUuid): AdminNotification
    {
        return $this->dispatch(
            'facture',
            "Nouvelle facture #{$factureRef}",
            "Facture créée automatiquement pour la commande #{$commandeUuid}",
            '/admin/factures',
            null,
            ['facture_ref' => $factureRef, 'commande_uuid' => $commandeUuid]
        );
    }

    // ──────────────────────────────────────────────────────────────
    //  Broadcast WebSocket (via fichier partagé)
    // ──────────────────────────────────────────────────────────────

    /**
     * Écrit la notification dans un fichier de broadcast que le serveur
     * WebSocket poll pour envoyer en temps réel aux clients connectés.
     */
    private function broadcastToWebSocket(AdminNotification $notification): void
    {
        try {
            $broadcastDir = storage_path('app/websocket');

            if (!is_dir($broadcastDir)) {
                mkdir($broadcastDir, 0755, true);
            }

            $payload = json_encode([
                'event' => 'admin.notification',
                'data'  => [
                    'id'           => $notification->id,
                    'type'         => $notification->type,
                    'titre'        => $notification->titre,
                    'message'      => $notification->message,
                    'lien'         => $notification->lien,
                    'expediteur'   => $notification->expediteur,
                    'meta'         => $notification->meta,
                    'lue'          => $notification->lue,
                    'date_creation'=> $notification->date_creation?->toISOString(),
                ],
            ], JSON_UNESCAPED_UNICODE);

            $filename = $broadcastDir . '/' . time() . '_' . $notification->id . '.json';
            file_put_contents($filename, $payload);
        } catch (\Throwable $e) {
            Log::warning('WebSocket broadcast failed', ['error' => $e->getMessage()]);
        }
    }
}
