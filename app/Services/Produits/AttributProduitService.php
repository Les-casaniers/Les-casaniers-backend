<?php

namespace App\Services\Produits;

use App\Repositories\AttributProduit\AttributProduitRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AttributProduitService
{
    protected $attributRepository;

    // Dictionnaire des clés standard pour le configurateur et l'affichage
    protected $standardKeys = [
        'socket' => ['label' => 'Socket', 'group' => 'Processeur'],
        'tdp' => ['label' => 'TDP (Watts)', 'group' => 'Énergie'],
        'ddr_generation' => ['label' => 'Type de Mémoire', 'group' => 'Mémoire'],
        'longueur_gpu_mm' => ['label' => 'Longueur GPU (mm)', 'group' => 'Dimensions'],
        'puissance_w' => ['label' => 'Puissance (Watts)', 'group' => 'Énergie'],
        'nb_coeurs' => ['label' => 'Nombre de cœurs', 'group' => 'Processeur'],
        'frequence_ghz' => ['label' => 'Fréquence (GHz)', 'group' => 'Processeur'],
        'format_cm' => ['label' => 'Format Carte Mère', 'group' => 'Dimensions'],
        'chipset' => ['label' => 'Chipset', 'group' => 'Carte Mère'],
        'capacite_go' => ['label' => 'Capacité (Go)', 'group' => 'Mémoire/Stockage'],
    ];

    public function __construct(AttributProduitRepositoryInterface $attributRepository)
    {
        $this->attributRepository = $attributRepository;
    }

    /**
     * Ajouter ou mettre à jour les attributs d'un produit
     */
    public function syncAttributes(int $produitId, array $attributes)
    {
        // On nettoie d'abord les anciens attributs pour ce produit
        $this->attributRepository->deleteByProduit($produitId);

        $created = [];
        foreach ($attributes as $attr) {
            $this->validateAttribute($attr);
            
            // Si le libellé n'est pas fourni, on utilise le dictionnaire
            if (empty($attr['libelle_attr']) && isset($this->standardKeys[$attr['cle_attr']])) {
                $attr['libelle_attr'] = $this->standardKeys[$attr['cle_attr']]['label'];
            }

            $attr['produit_id'] = $produitId;
            $created[] = $this->attributRepository->create($attr);
        }

        return $created;
    }

    /**
     * Récupérer les attributs formatés pour l'affichage technique
     */
    public function getTechnicalSheet(int $produitId)
    {
        $attributes = $this->attributRepository->findByProduit($produitId);
        
        return $attributes->map(function ($attr) {
            $config = $this->standardKeys[$attr->cle_attr] ?? null;
            return [
                'cle' => $attr->cle_attr,
                'label' => $attr->libelle_attr ?: ($config['label'] ?? ucfirst($attr->cle_attr)),
                'valeur' => $attr->valeur_attr,
                'groupe' => $config['group'] ?? 'Autres'
            ];
        })->groupBy('groupe');
    }

    /**
     * Vérifier un attribut spécifique (utile pour le configurateur/compatibilité)
     */
    public function getAttributeValue(int $produitId, string $key)
    {
        $attributes = $this->attributRepository->findByProduit($produitId);
        $attr = $attributes->where('cle_attr', $key)->first();
        
        return $attr ? $attr->valeur_attr : null;
    }

    /**
     * Retourne la liste des clés standard pour le Back-Office
     */
    public function getStandardKeys()
    {
        return $this->standardKeys;
    }

    /**
     * Validation d'un attribut
     */
    protected function validateAttribute(array $data)
    {
        $validator = Validator::make($data, [
            'cle_attr' => 'required|string|max:80',
            'libelle_attr' => 'nullable|string|max:120',
            'valeur_attr' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
