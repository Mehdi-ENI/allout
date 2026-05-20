<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;


class SortieService
{
    public function __construct(private EntityManagerInterface         $entityManager,
                                private readonly SortieRepository      $sortieRepository,
                                private Security                       $security,
                                private readonly ParticipantRepository $participantRepository)
    {
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

    public function getSortieDetail(int $id): Sortie
    {

        $sortie = $this->sortieRepository->find($id);

        if (!$sortie) {
            throw new \Exception("Ooooops ! Sortie not found !");
        }

        return $sortie;
    }

    public function inscription(int $id): void
    {
        $sortie = $this->sortieRepository->find($id);
        if (!$sortie) {
            throw new \Exception("Ooooops ! Sortie not found !");
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw new \Exception("Ooooops ! Vous devez être connecté !");
        }

        $participant = $this->participantRepository->findOneBy(['email' => $currentUser->getUserIdentifier()]);
        if (!$participant) {
            throw new \Exception("Ooooops ! Participant non trouvé");
        }

        if ($sortie->getEtat()->value == "publiee" && !$sortie->getParticipants()->contains($participant)) {
            $sortie->addParticipant($participant);
            $this->entityManager->persist($sortie);
            $this->entityManager->flush();
        } else {
            throw new \Exception("Ooooops ! Inscription non autorisée !");
        }
    }

    public function desistement(int $id): void
    {
        $sortie = $this->sortieRepository->find($id);
        if (!$sortie) {
            throw new \Exception("Ooooops ! Sortie not found !");
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw new \Exception("Ooooops ! Vous devez être connecté !");
        }

        $participant = $this->participantRepository->findOneBy(['email' => $currentUser->getUserIdentifier()]);
        if (!$participant) {
            throw new \Exception("Ooooops ! Participant non trouvé");
        }

        if ($sortie->getEtat()->value == "publiee" && $sortie->getParticipants()->contains($participant)) {
            $sortie->removeParticipant($participant);
            $this->entityManager->persist($sortie);
            $this->entityManager->flush();
        } else {
            throw new \Exception("Ooooops ! Désistement non autorisée !");
        }
    }
}
