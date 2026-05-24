<?php

namespace App\Services\Produits;

use App\Repositories\Produit\ProduitRepositoryInterface;
use App\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProduitService
{
    protected $produitRepository;
    protected $categoryRepository;

    public function __construct(
        ProduitRepositoryInterface $produitRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->produitRepository = $produitRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * CRUD Back-Office: Creer un produit
     */
    public function createProduit(array $data)
    {
        unset($data['reference']);

        $this->validateProduit($data);
        $data['est_dispo'] = ((int) ($data['quantite_stock'] ?? 0)) > 0;

        $category = $this->categoryRepository->findById($data['categorie_id']);
        if (!$category) {
            throw new Exception("La categorie specifiee n'existe pas.");
        }

        $data['reference'] = $this->generateReference($category->code);

        return $this->produitRepository->create($data);
    }

    /**
     * CRUD Back-Office: Mettre a jour un produit
     */
    public function updateProduit(int $id, array $data)
    {
        // On ne valide que les champs fournis pour l'edition partielle
        $validator = Validator::make($data, [
            'prix' => 'sometimes|numeric|min:0',
            'quantite_stock' => 'sometimes|integer|min:0',
            'description' => 'sometimes|nullable|string',
            'description_courte' => 'sometimes|nullable|string|max:500',
            'nom' => 'sometimes|string|max:255',
            'categorie_id' => 'sometimes|exists:categories,id',
            'type_produit' => 'sometimes|in:pc,portable,pro,gaming,composant,peripherique,service',
            'reference' => 'sometimes|nullable|string|max:80|unique:produits,reference,' . $id,
            'devise' => 'sometimes|nullable|string|max:10',
            'actif' => 'sometimes|boolean',
            'est_dispo' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        unset($data['reference']);
        unset($data['est_dispo']);

        return $this->produitRepository->update($id, $data);
    }

    /**
     * Activation / Desactivation
     */
    public function toggleStatus(int $id, bool $active)
    {
        return $this->produitRepository->update($id, ['actif' => $active]);
    }

    /**
     * CRUD Back-Office: Supprimer un produit
     */
    public function deleteProduit(int $id)
    {
        return $this->produitRepository->delete($id);
    }

    /**
     * Gestion du stock : decremente atomiquement sur commande confirmee.
     * Utilise une requete SQL atomique pour eviter les race conditions.
     *
     * @throws \Exception Si le stock est insuffisant.
     */
    public function decrementStock(int $produitId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $success = $this->produitRepository->decrementStock($produitId, $quantity);

        if (!$success) {
            $produit = $this->produitRepository->findById($produitId);
            $nom = $produit?->nom ?? "ID #{$produitId}";
            $stockActuel = $produit?->quantite_stock ?? 0;
            throw new Exception(
                "Stock insuffisant pour \"{$nom}\" (demande: {$quantity}, disponible: {$stockActuel})."
            );
        }
    }

    /**
     * Gestion du stock : restaure le stock apres annulation ou remboursement.
     */
    public function restoreStock(int $produitId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $this->produitRepository->incrementStock($produitId, $quantity);
    }

    /**
     * Recuperer un produit par ID
     */
    public function getProduitById(int $id)
    {
        return $this->produitRepository->findById($id);
    }

    /**
     * Liste des produits par categorie
     */
    public function getProduitsByCategory(int $categoryId)
    {
        return $this->produitRepository->findByCategory($categoryId);
    }

    /**
     * Liste des produits avec filtres et recherche
     */
    public function getProduits(array $filters = [], ?string $search = null)
    {
        $produits = $this->produitRepository->getAll($filters);

        if ($search) {
            $needle = mb_strtolower($search);
            $produits = $produits->filter(function ($produit) use ($needle) {
                $nom = mb_strtolower((string) $produit->nom);
                $reference = mb_strtolower((string) $produit->reference);
                $description = mb_strtolower((string) $produit->description);
                return str_contains($nom, $needle)
                    || str_contains($reference, $needle)
                    || str_contains($description, $needle);
            })->values();
        }

        return $produits;
    }

    /**
     * Validation pour la creation
     */
    protected function validateProduit(array $data)
    {
        $validator = Validator::make($data, [
            'nom' => 'required|string|max:255',
            'categorie_id' => 'required|exists:categories,id',
            'type_produit' => 'required|in:pc,portable,pro,gaming,composant,peripherique,service',
            'prix' => 'required|numeric|min:0',
            'quantite_stock' => 'required|integer|min:0',
            'reference' => 'nullable|string|max:80|unique:produits,reference',
            'description' => 'nullable|string',
            'description_courte' => 'nullable|string|max:500',
            'devise' => 'nullable|string|max:10',
            'actif' => 'boolean',
            'est_dispo' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Genere la prochaine reference sequentielle selon le code categorie.
     */
    protected function generateReference(?string $categoryCode): string
    {
        $prefix = $this->normalizeReferencePrefix($categoryCode);
        $lastReference = $this->produitRepository->getLastReferenceByPrefix($prefix);

        if (!$lastReference) {
            return $prefix . '001';
        }

        $numericPart = (int) substr($lastReference, strlen($prefix));
        $nextNumber = $numericPart + 1;

        return sprintf('%s%03d', $prefix, $nextNumber);
    }

    protected function normalizeReferencePrefix(?string $categoryCode): string
    {
        $prefix = strtoupper(trim((string) $categoryCode));

        if ($prefix === '') {
            throw new Exception("La categorie doit avoir un code pour generer la reference du produit.");
        }

        return str_ends_with($prefix, '-') ? $prefix : $prefix . '-';
    }
}
