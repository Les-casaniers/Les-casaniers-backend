<?php

// namespace App\Enums\Sales;

// enum CommandeStatut: string
// {
//     case EnAttente = 'en_attente';
//     case Payee = 'payee';
//     case EnTraitement = 'en_traitement';
//     case Expediee = 'expediee';
//     case Terminee = 'terminee';
//     case Annulee = 'annulee';
//     case Remboursee = 'remboursee';

//     /**
//      * Transitions autorisées depuis ce statut.
//      *
//      * @return self[]
//      */
//     public function transitionsAutorisees(): array
//     {
//         return match ($this) {
//             self::EnAttente    => [self::Payee, self::Annulee],
//             self::Payee        => [self::EnTraitement, self::Annulee, self::Remboursee],
//             self::EnTraitement => [self::Expediee, self::Annulee, self::Remboursee],
//             self::Expediee     => [self::Terminee, self::Remboursee],
//             self::Terminee     => [self::Remboursee],
//             self::Annulee      => [],
//             self::Remboursee   => [],
//         };
//     }

//     /**
//      * Vérifie si la transition vers le statut cible est autorisée.
//      */
//     public function peutTransitionVers(self $cible): bool
//     {
//         return in_array($cible, $this->transitionsAutorisees(), true);
//     }

//     /**
//      * Vérifie si la commande est annulable.
//      */
//     public function estAnnulable(): bool
//     {
//         return $this->peutTransitionVers(self::Annulee);
//     }

//     /**
//      * Vérifie si la commande est dans un état terminal.
//      */
//     public function estTerminale(): bool
//     {
//         return $this === self::Annulee || $this === self::Remboursee;
//     }

//     /**
//      * Label lisible pour l'affichage.
//      */
//     public function label(): string
//     {
//         return match ($this) {
//             self::EnAttente    => 'En attente',
//             self::Payee        => 'Payée',
//             self::EnTraitement => 'En traitement',
//             self::Expediee     => 'Expédiée',
//             self::Terminee     => 'Terminée',
//             self::Annulee      => 'Annulée',
//             self::Remboursee   => 'Remboursée',
//         };
//     }
// }
namespace App\Enums\Sales;

enum CommandeStatut: string
{
    case EnAttente = 'en_attente';
    case Payee = 'payee';
    case EnTraitement = 'en_traitement';
    case Expediee = 'expediee';
    case Terminee = 'terminee';
    case Annulee = 'annulee';
    case Remboursee = 'remboursee';

    /**
     * Transitions autorisées depuis ce statut.
     *
     * @return self[]
     */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::EnAttente    => [self::Payee, self::Annulee],
            self::Payee        => [self::EnTraitement, self::Annulee, self::Remboursee],
            self::EnTraitement => [self::Expediee, self::Annulee, self::Remboursee],
            self::Expediee     => [self::Terminee, self::Remboursee],
            self::Terminee     => [self::Remboursee],
            self::Annulee      => [],
            self::Remboursee   => [],
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
     * Vérifie si la commande est annulable.
     */
    public function estAnnulable(): bool
    {
        return $this->peutTransitionVers(self::Annulee);
    }

    /**
     * Vérifie si la commande est dans un état terminal.
     */
    public function estTerminale(): bool
    {
        return $this === self::Annulee || $this === self::Remboursee;
    }

    /**
     * Label lisible pour l'affichage.
     */
    public function label(): string
    {
        return match ($this) {
            self::EnAttente    => 'En attente',
            self::Payee        => 'Payée',
            self::EnTraitement => 'En traitement',
            self::Expediee     => 'Expédiée',
            self::Terminee     => 'Terminée',
            self::Annulee      => 'Annulée',
            self::Remboursee   => 'Remboursée',
        };
    }

    /**
     * Couleur pour l'affichage.
     */
    public function color(): string
    {
        return match ($this) {
            self::EnAttente    => 'warning',
            self::Payee        => 'success',
            self::EnTraitement => 'info',
            self::Expediee     => 'primary',
            self::Terminee     => 'success',
            self::Annulee      => 'danger',
            self::Remboursee   => 'secondary',
        };
    }
}
