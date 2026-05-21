<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;

class SortieService{
    public function __construct(private EntityManagerInterface    $entityManager,
                                private readonly SortieRepository $sortieRepository) {
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

    public function getSortieDetail(int $id): Sortie {

        $sortie = $this->sortieRepository->find($id);

        if (!$sortie) {
            throw new \Exception("Ooooops ! Sortie not found !");
        }

        return $sortie;
    }

    /**
     * @throws \Exception
     */
    public function annulerSortie(Sortie $sortie, string $motif): void
    {
        $now = new \DateTime();

        if ($sortie->getDateHeureDebut() <= $now) {
            throw new \Exception('Impossible d’annuler une sortie déjà commencée ou terminée.');
        }

        $sortie->setAnnulee(true);
        $sortie->setMotifAnnulation($motif);

        foreach ($sortie->getParticipants() as $participant) {

            // envoi mail

        }

        $this->entityManager->flush();
    }
}
