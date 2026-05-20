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
}
