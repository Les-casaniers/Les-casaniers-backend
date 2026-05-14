<?php

namespace App\Traits;

use Illuminate\Support\Collection;

/**
 * Trait partagé pour le calcul des montants à partir d'items ligne.
 *
 * Chaque item doit posséder les propriétés `prix_unitaire` et `quantite`.
 */
trait CalculatesLineItems
{
    /**
     * Calcule le sous-total à partir d'une collection ou d'un tableau d'items.
     *
     * @param  Collection|array  $items
     */
    protected function calculerSousTotal(Collection|array $items): float
    {
        $collection = $items instanceof Collection ? $items : collect($items);

        return (float) $collection->sum(
            fn ($item) => ((float) ($item->prix_unitaire ?? 0)) * ((int) ($item->quantite ?? 0))
        );
    }

    /**
     * Calcule le montant d'une seule ligne (prix_unitaire × quantite).
     */
    protected function calculerLigne(object $item): float
    {
        return ((float) ($item->prix_unitaire ?? 0)) * ((int) ($item->quantite ?? 0));
    }
}
