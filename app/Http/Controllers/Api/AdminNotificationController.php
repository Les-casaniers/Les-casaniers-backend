<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly AdminNotificationService $notificationService
    ) {
    }

    /**
     * GET /admin/notifications
     * Liste toutes les notifications avec filtres optionnels.
     */
    public function index(Request $request)
    {
        $filtre = $request->query('filtre');   // toutes, non-lues, lues
        $type   = $request->query('type');     // commande, produit, client, etc.
        $limit  = (int) ($request->query('limit', 50));

        $notifications = $this->notificationService->getAll($filtre, $type, $limit);

        return response()->json([
            'success' => true,
            'data'    => $notifications,
            'meta'    => [
                'non_lues' => $this->notificationService->countUnread(),
            ],
        ]);
    }

    /**
     * GET /admin/notifications/count
     * Retourne uniquement le nombre de notifications non lues.
     */
    public function count()
    {
        return response()->json([
            'success'  => true,
            'non_lues' => $this->notificationService->countUnread(),
        ]);
    }

    /**
     * PATCH /admin/notifications/{id}/lire
     * Marquer une notification comme lue.
     */
    public function markAsRead(int $id)
    {
        $success = $this->notificationService->markAsRead($id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification introuvable.',
            ], 404);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Notification marquée comme lue.',
            'non_lues' => $this->notificationService->countUnread(),
        ]);
    }

    /**
     * PATCH /admin/notifications/lire-tout
     * Marquer toutes les notifications comme lues.
     */
    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead();

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) marquée(s) comme lue(s).",
            'non_lues' => 0,
        ]);
    }

    /**
     * DELETE /admin/notifications/{id}
     * Supprimer une notification.
     */
    public function destroy(int $id)
    {
        $success = $this->notificationService->delete($id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification introuvable.',
            ], 404);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Notification supprimée.',
            'non_lues' => $this->notificationService->countUnread(),
        ]);
    }

    /**
     * DELETE /admin/notifications
     * Supprimer toutes les notifications.
     */
    public function destroyAll()
    {
        $count = $this->notificationService->deleteAll();

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) supprimée(s).",
            'non_lues' => 0,
        ]);
    }
}
