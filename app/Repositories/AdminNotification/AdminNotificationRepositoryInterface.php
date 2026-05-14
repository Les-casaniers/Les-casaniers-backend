<?php

namespace App\Repositories\AdminNotification;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Collection;

interface AdminNotificationRepositoryInterface
{
    public function all(?string $filtre = null, ?string $type = null, int $limit = 50): Collection;

    public function find(int $id): ?AdminNotification;

    public function create(array $data): AdminNotification;

    public function markAsRead(int $id): bool;

    public function markAllAsRead(): int;

    public function delete(int $id): bool;

    public function deleteAll(): int;

    public function countUnread(): int;
}
