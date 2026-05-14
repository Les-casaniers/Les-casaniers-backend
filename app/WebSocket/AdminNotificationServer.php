<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * Serveur WebSocket pour les notifications admin en temps réel.
 *
 * Architecture :
 * - Les clients (navigateurs admin) se connectent via ws:// ou wss://
 * - Un timer poll le répertoire storage/app/websocket/ pour les nouvelles
 *   notifications émises par le backend Laravel (via AdminNotificationService)
 * - Les messages sont diffusés à tous les clients connectés
 */
class AdminNotificationServer implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface, mixed> */
    protected \SplObjectStorage $clients;

    /** @var string Répertoire de broadcast */
    protected string $broadcastDir;

    /** @var array<string> Fichiers déjà traités */
    protected array $processedFiles = [];

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->broadcastDir = storage_path('app/websocket');

        if (!is_dir($this->broadcastDir)) {
            mkdir($this->broadcastDir, 0755, true);
        }

        echo "[WS] Serveur WebSocket de notifications admin démarré.\n";
        echo "[WS] En attente de connexions...\n";
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        echo "[WS] Nouvelle connexion ({$conn->resourceId}). Clients connectés: {$this->clients->count()}\n";

        // Envoyer un message de bienvenue
        $conn->send(json_encode([
            'event' => 'connected',
            'data'  => [
                'message'   => 'Connexion WebSocket établie.',
                'timestamp' => date('c'),
            ],
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $decoded = json_decode($msg, true);

        // Supporter un ping/pong pour le keepalive
        if (isset($decoded['event']) && $decoded['event'] === 'ping') {
            $from->send(json_encode(['event' => 'pong', 'data' => ['timestamp' => date('c')]]));
            return;
        }

        echo "[WS] Message reçu du client {$from->resourceId}: {$msg}\n";
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        echo "[WS] Connexion fermée ({$conn->resourceId}). Clients connectés: {$this->clients->count()}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "[WS] Erreur ({$conn->resourceId}): {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Appelé périodiquement par le timer du serveur pour vérifier
     * les nouveaux fichiers de broadcast.
     */
    public function checkBroadcasts(): void
    {
        if (!is_dir($this->broadcastDir)) {
            return;
        }

        $files = glob($this->broadcastDir . '/*.json');

        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            $basename = basename($file);

            // Ne pas retraiter un fichier déjà envoyé
            if (in_array($basename, $this->processedFiles, true)) {
                continue;
            }

            $content = @file_get_contents($file);

            if (!$content) {
                continue;
            }

            // Diffuser à tous les clients connectés
            foreach ($this->clients as $client) {
                try {
                    $client->send($content);
                } catch (\Throwable $e) {
                    echo "[WS] Erreur d'envoi au client {$client->resourceId}: {$e->getMessage()}\n";
                }
            }

            $this->processedFiles[] = $basename;

            // Supprimer le fichier après envoi
            @unlink($file);

            echo "[WS] Notification diffusée à {$this->clients->count()} client(s).\n";
        }

        // Limiter la taille du tableau des fichiers traités
        if (count($this->processedFiles) > 500) {
            $this->processedFiles = array_slice($this->processedFiles, -100);
        }
    }
}
