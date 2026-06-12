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
     * CRUD Back-Office: Créer un produit
     */
    public function createProduit(array $data)
    {
        $this->validateProduit($data);
        $data['est_dispo'] = ((int) ($data['quantite_stock'] ?? 0)) > 0;

        if (empty($data['reference'])) {
            $data['reference'] = $this->generateReference();
        }

        // Vérifier si la catégorie existe
        $category = $this->categoryRepository->findById($data['categorie_id']);
        if (!$category) {
            throw new Exception("La catégorie spécifiée n'existe pas.");
        }

        return $this->produitRepository->create($data);
    }

    /**
     * CRUD Back-Office: Mettre à jour un produit
     */
    public function updateProduit(int $id, array $data)
    {
        // On ne valide que les champs fournis pour l'édition partielle
        $validator = Validator::make($data, [
            'prix' => 'sometimes|numeric|min:0',
            'quantite_stock' => 'sometimes|integer|min:0',
            'description' => 'sometimes|nullable|string',
            'description_courte' => 'sometimes|nullable|string|max:500',
            'atout' => 'sometimes|nullable|string|max:255',
            'nom' => 'sometimes|string|max:255',
            'categorie_id' => 'sometimes|exists:categories,id',
            'id_sous_categorie' => 'sometimes|nullable|exists:sous_categories,id',
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
     * Activation / Désactivation
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
     * Gestion du stock : décrémente atomiquement sur commande confirmée.
     * Utilise une requête SQL atomique pour éviter les race conditions.
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
                "Stock insuffisant pour \"{$nom}\" (demandé: {$quantity}, disponible: {$stockActuel})."
            );
        }
    }

    /**
     * Gestion du stock : restaure le stock après annulation ou remboursement.
     */
    public function restoreStock(int $produitId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $this->produitRepository->incrementStock($produitId, $quantity);
    }

    /**
     * Récupérer un produit par ID
     */
    public function getProduitById(int $id)
    {
        return $this->produitRepository->findById($id);
    }

    /**
     * Liste des produits par catégorie
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
     * Validation pour la création
     */
    protected function validateProduit(array $data)
    {
        $validator = Validator::make($data, [
            'nom' => 'required|string|max:255',
            'categorie_id' => 'required|exists:categories,id',
            'id_sous_categorie' => 'nullable|exists:sous_categories,id',
            'prix' => 'required|numeric|min:0',
            'quantite_stock' => 'required|integer|min:0',
            'reference' => 'nullable|string|max:80|unique:produits,reference',
            'description' => 'nullable|string',
            'description_courte' => 'nullable|string|max:500',
            'atout' => 'nullable|string|max:255',
            'devise' => 'nullable|string|max:10',
            'actif' => 'boolean',
            'est_dispo' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Génère la prochaine référence séquentielle au format REF-001.
     */
    protected function generateReference(): string
    {
        $lastReference = $this->produitRepository->getLastReference();

        if (!$lastReference) {
            return 'REF-001';
        }

        $numericPart = (int) preg_replace('/^REF-/', '', $lastReference);
        $nextNumber = $numericPart + 1;

        return sprintf('REF-%03d', $nextNumber);
    }
}
