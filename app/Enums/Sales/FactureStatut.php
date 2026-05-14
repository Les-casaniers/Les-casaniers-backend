<?php

namespace App\Enums\Sales;

enum FactureStatut: string
{
    case Brouillon = 'brouillon';
    case Emise = 'emise';
    case Payee = 'payee';
    case Annulee = 'annulee';

    /**
     * Transitions autorisées depuis ce statut.
     *
     * @return self[]
     */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::Brouillon => [self::Emise, self::Annulee],
            self::Emise     => [self::Payee, self::Annulee],
            self::Payee     => [],
            self::Annulee   => [],
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
     * Vérifie si la facture est annulable.
     */
    public function estAnnulable(): bool
    {
        return $this->peutTransitionVers(self::Annulee);
    }

    /**
     * Vérifie si le document PDF peut être téléchargé.
     */
    public function estTelechargeable(): bool
    {
        return $this !== self::Brouillon;
    }

    /**
     * Label lisible pour l'affichage.
     */
    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Emise     => 'Émise',
            self::Payee     => 'Payée',
            self::Annulee   => 'Annulée',
        };
    }
}
