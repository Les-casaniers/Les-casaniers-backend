<?php

namespace App\Repositories\Guides;

use App\Models\Guide;

class GuideRepository implements GuideRepositoryInterface
{
    public function __construct(protected Guide $model)
    {
    }

    public function paginate(array $filters = [], bool $admin = false)
    {
        $query = $this->model->newQuery();

        if (!$admin) {
            $query->published();
        } elseif (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['categorie'])) {
            $query->where('categorie', $filters['categorie']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('resume', 'like', "%{$search}%")
                    ->orWhere('contenu', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['badge'])) {
            $query->where('badge', $filters['badge']);
        }

        if (!empty($filters['mis_en_avant'])) {
            $query->where('mis_en_avant', true);
        }

        if (($filters['sort'] ?? 'recent') === 'popular') {
            $query->orderByDesc('popularite')->orderByDesc('vues');
        } else {
            $query->orderBy('ordre')->orderByDesc('publie_le')->orderByDesc('date_creation');
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 9), 1), 50);
        return $query->paginate($perPage);
    }

    public function recent(int $limit = 4)
    {
        return $this->model->newQuery()
            ->published()
            ->orderByDesc('publie_le')
            ->orderByDesc('date_creation')
            ->limit(min(max($limit, 1), 12))
            ->get();
    }

    public function popular(int $limit = 4)
    {
        return $this->model->newQuery()
            ->published()
            ->orderByDesc('popularite')
            ->orderByDesc('vues')
            ->limit(min(max($limit, 1), 12))
            ->get();
    }

    public function featured(int $limit = 6)
    {
        return $this->model->newQuery()
            ->published()
            ->where('mis_en_avant', true)
            ->orderBy('ordre')
            ->orderByDesc('publie_le')
            ->limit(min(max($limit, 1), 12))
            ->get();
    }

    public function byCategory(string $categorie, int $limit = 6)
    {
        return $this->model->newQuery()
            ->published()
            ->where('categorie', $categorie)
            ->orderBy('ordre')
            ->orderByDesc('publie_le')
            ->limit(min(max($limit, 1), 20))
            ->get();
    }

    public function categories(): array
    {
        return $this->model->newQuery()
            ->published()
            ->select('categorie')
            ->distinct()
            ->orderBy('categorie')
            ->pluck('categorie')
            ->filter()
            ->values()
            ->all();
    }

    public function findPublicById(int $id)
    {
        return $this->model->newQuery()->published()->find($id);
    }

    public function findBySlug(string $slug)
    {
        return $this->model->newQuery()->published()->where('slug', $slug)->first();
    }

    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $guide = $this->findById($id);
        if (!$guide) {
            return null;
        }

        $guide->update($data);
        return $guide->fresh();
    }

    public function delete(int $id): bool
    {
        $guide = $this->findById($id);
        if (!$guide) {
            return false;
        }

        return (bool) $guide->delete();
    }
}
