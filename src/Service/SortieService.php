<?php

namespace App\Service;

use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;

class SortieService{
    public function __construct(private EntityManagerInterface $entityManager) {
    }

    public function creerSortie(Sortie $sortie): void
    {
        // Vérification métier
//        if ($sortie->getDateLimiteInscription() > $sortie->getDateHeureDebut()) {
//            throw new \Exception("La date de clôture doit être avant la sortie.");
//        }

        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }
}
