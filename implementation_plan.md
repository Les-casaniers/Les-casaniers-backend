# Plan d'implémentation — Notifications Admin en Temps Réel

## Architecture

```
┌─────────────────┐   TCP Push (6002)   ┌───────────────────────┐   WebSocket (6001)   ┌──────────────┐
│   Laravel API   │ ─────────────────→  │  Ratchet WS Server    │ ───────────────────→ │  React SPA   │
│   (port 8000)   │                     │  (artisan command)    │                      │  (port 8080) │
└─────────────────┘                     └───────────────────────┘                      └──────────────┘
        │                                                                                      │
        │                              REST API (CRUD)                                         │
        └──────────────────────────────────────────────────────────────────────────────────────┘
```

## Fichiers Backend (Laravel)

| # | Fichier | Action |
|---|---------|--------|
| 1 | `composer.json` | Ajouter `cboden/ratchet` |
| 2 | `database/migrations/xxx_create_admin_notifications_table.php` | **Créer** |
| 3 | `app/Models/AdminNotification.php` | **Créer** |
| 4 | `app/Services/AdminNotificationService.php` | **Créer** |
| 5 | `app/Http/Controllers/Api/AdminNotificationController.php` | **Créer** |
| 6 | `app/Console/Commands/WebSocketServe.php` | **Créer** |
| 7 | `routes/api.php` | Ajouter routes notifications |
| 8 | `app/Services/UtilisateurService.php` | Intégrer notifs (inscription) |
| 9 | `app/Services/Sales/CommandeService.php` | Intégrer notifs (commandes) |
| 10 | `app/Services/Produits/ProduitService.php` | Intégrer notifs (stock) |
| 11 | `.env` | Ajouter WS config |

## Fichiers Frontend (React)

| # | Fichier | Action |
|---|---------|--------|
| 12 | `src/service/websocket.ts` | **Créer** — Gestionnaire WebSocket |
| 13 | `src/hooks/useNotifications.ts` | **Créer** — Hook WS + REST |
| 14 | `src/components/ActionAdmin/AdminNotifications.tsx` | **Réécrire** — Utiliser données réelles |

## Événements Notifiés

- ✅ Nouvel utilisateur inscrit
- ✅ Nouvelle commande créée
- ✅ Changement de statut commande (payée, expédiée, annulée, remboursée, etc.)
- ✅ Alerte stock faible (≤ 5 unités)
- ✅ Rupture de stock (0 unité)
