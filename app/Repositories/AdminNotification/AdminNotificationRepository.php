<?php

namespace App\Repositories\AdminNotification;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Collection;

class AdminNotificationRepository implements AdminNotificationRepositoryInterface
{
    public function all(?string $filtre = null, ?string $type = null, int $limit = 50): Collection
    {
        $query = AdminNotification::query();

        if ($filtre === 'non-lues') {
            $query->where('lue', false);
        } elseif ($filtre === 'lues') {
            $query->where('lue', true);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderByDesc('date_creation')->limit($limit)->get();
    }

    public function find(int $id): ?AdminNotification
    {
        return AdminNotification::find($id);
    }

    public function create(array $data): AdminNotification
    {
        return AdminNotification::create($data);
    }

    public function markAsRead(int $id): bool
    {
        return (bool) AdminNotification::where('id', $id)->update([
            'lue'          => true,
            'date_lecture' => now(),
        ]);
    }

    public function markAllAsRead(): int
    {
        return AdminNotification::where('lue', false)->update([
            'lue'          => true,
            'date_lecture' => now(),
        ]);
    }

    public function delete(int $id): bool
    {
        return (bool) AdminNotification::destroy($id);
    }

    public function deleteAll(): int
    {
        return AdminNotification::query()->delete();
    }

    public function countUnread(): int
    {
        return AdminNotification::where('lue', false)->count();
    }
}
