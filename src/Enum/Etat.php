<?php

namespace App\Enum;

enum Etat: string
{
    case Creee = 'creee';
    case Publiee = 'publiee';
    case Cloturee = 'cloturee';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case Archivee = 'archivee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match($this) {
            self::Creee => 'Créée',
            self::Publiee => 'Publiée',
            self::Cloturee => 'Clôturée',
            self::EnCours => 'En cours',
            self::Terminee => 'Terminée',
            self::Archivee => 'Archivée',
            self::Annulee => 'Annulée',
        };
    }

}
