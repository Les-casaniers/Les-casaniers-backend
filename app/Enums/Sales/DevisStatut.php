<?php

namespace App\Enums\Sales;

enum DevisStatut: string
{
    case Brouillon = 'brouillon';
    case Envoye = 'envoye';
    case Accepte = 'accepte';
    case Refuse = 'refuse';
    case Expire = 'expire';

    /**
     * Transitions autorisées depuis ce statut.
     *
     * @return self[]
     */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::Brouillon => [self::Envoye, self::Expire],
            self::Envoye    => [self::Accepte, self::Refuse, self::Expire],
            self::Accepte   => [],
            self::Refuse    => [],
            self::Expire    => [],
        };
    }

    /**
     * Vérifie si la transition vers le statut cible est autorisée.
     */
    public function peutTransitionVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }

    /**
     * Vérifie si le devis est modifiable (contenu).
     */
    public function estModifiable(): bool
    {
        return $this === self::Brouillon;
    }

    /**
     * Vérifie si le devis est supprimable.
     */
    public function estSupprimable(): bool
    {
        return $this === self::Brouillon;
    }

    /**
     * Label lisible pour l'affichage.
     */
    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Envoye    => 'Envoyé',
            self::Accepte   => 'Accepté',
            self::Refuse    => 'Refusé',
            self::Expire    => 'Expiré',
        };
    }
}
