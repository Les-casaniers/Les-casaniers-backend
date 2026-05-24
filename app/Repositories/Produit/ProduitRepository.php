<?php

namespace App\Repositories\Produit;

use App\Models\Produit;

class ProduitRepository implements ProduitRepositoryInterface
{
    protected $model;

    public function __construct(Produit $produit)
    {
        $this->model = $produit;
    }

    public function getAll(array $filters = [])
    {
        $query = $this->model->with(['categorie', 'images']);

        if (isset($filters['categorie_id'])) {
            $query->where('categorie_id', $filters['categorie_id']);
        }

        if (isset($filters['actif'])) {
            $query->where('actif', $filters['actif']);
        }

        if (isset($filters['type_produit'])) {
            $query->where('type_produit', $filters['type_produit']);
        }

        if (isset($filters['est_dispo'])) {
            $query->where('est_dispo', $filters['est_dispo']);
        }

        return $query->get();
    }

    public function findById(int $id)
    {
        return $this->model->with(['categorie', 'images', 'attributs', 'configurations'])->find($id);
    }

    public function findByCategory(int $categoryId)
    {
        return $this->model->where('categorie_id', $categoryId)
            ->with(['images'])
            ->get();
    }

    public function create(array $data)
    {
        if (array_key_exists('quantite_stock', $data) && !array_key_exists('est_dispo', $data)) {
            $data['est_dispo'] = ((int) $data['quantite_stock']) > 0;
        }
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $produit = $this->findById($id);
        if ($produit) {
            if (array_key_exists('quantite_stock', $data) && !array_key_exists('est_dispo', $data)) {
                $data['est_dispo'] = ((int) $data['quantite_stock']) > 0;
            }
            $produit->update($data);
            return $produit;
        }
        return null;
    }

    public function delete(int $id)
    {
        $produit = $this->findById($id);
        if ($produit) {
            return $produit->delete();
        }
        return false;
    }

    public function search(string $term)
    {
        return $this->model->where('nom', 'LIKE', "%{$term}%")
            ->orWhere('reference', 'LIKE', "%{$term}%")
            ->with(['categorie', 'images'])
            ->get();
    }

    public function getLatest(int $limit = 10)
    {
        return $this->model->orderBy('date_creation', 'DESC')
            ->limit($limit)
            ->with(['categorie', 'images'])
            ->get();
    }

    public function getLastReference(): ?string
    {
        return $this->model
            ->where('reference', 'like', 'REF-%')
            ->orderByRaw("CAST(SUBSTRING(reference, 5) AS UNSIGNED) DESC")
            ->value('reference');
    }

    public function getLastReferenceByPrefix(string $prefix): ?string
    {
        return $this->model
            ->where('reference', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(reference, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('reference');
    }

    /**
     * Décrémentation atomique : UPDATE WHERE quantite_stock >= quantity
     * Empêche les stocks négatifs même en cas de requêtes concurrentes.
     */
    public function decrementStock(int $id, int $quantity): bool
    {
        $affected = $this->model->newQuery()
            ->where('id', $id)
            ->where('quantite_stock', '>=', $quantity)
            ->update([
                'quantite_stock' => \DB::raw("quantite_stock - {$quantity}"),
                'est_dispo' => \DB::raw("CASE WHEN (quantite_stock - {$quantity}) > 0 THEN 1 ELSE 0 END"),
            ]);

        return $affected > 0;
    }

    /**
     * Restauration du stock après annulation/remboursement.
     */
    public function incrementStock(int $id, int $quantity): void
    {
        $this->model->newQuery()
            ->where('id', $id)
            ->update([
                'quantite_stock' => \DB::raw("quantite_stock + {$quantity}"),
                'est_dispo' => \DB::raw("CASE WHEN (quantite_stock + {$quantity}) > 0 THEN 1 ELSE 0 END"),
            ]);
    }
}
