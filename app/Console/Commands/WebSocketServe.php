<?php

namespace App\Console\Commands;

use App\WebSocket\AdminNotificationServer;
use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

class WebSocketServe extends Command
{
    protected $signature = 'websocket:serve {--port=8090 : Port d\'écoute du serveur WebSocket} {--host=0.0.0.0 : Adresse d\'écoute}';

    protected $description = 'Démarre le serveur WebSocket pour les notifications admin en temps réel';

    public function handle(): int
    {
        $port = (int) $this->option('port');
        $host = $this->option('host');

        $this->info("🚀 Démarrage du serveur WebSocket sur {$host}:{$port}...");

        $loop = Loop::get();

        $notifServer = new AdminNotificationServer();

        $wsServer = new WsServer($notifServer);
        $wsServer->enableKeepAlive($loop, 30);

        $httpServer = new HttpServer($wsServer);

        $socket = new SocketServer("{$host}:{$port}", [], $loop);
        $server = new IoServer($httpServer, $socket, $loop);

        // Timer pour vérifier les nouvelles notifications toutes les secondes
        $loop->addPeriodicTimer(1.0, function () use ($notifServer) {
            $notifServer->checkBroadcasts();
        });

        $this->info("✅ Serveur WebSocket démarré sur ws://{$host}:{$port}");
        $this->info("   Utilisez wss:// en production derrière un reverse proxy SSL.");
        $this->info("   Ctrl+C pour arrêter.\n");

        $server->run();

        return self::SUCCESS;
    }
}
