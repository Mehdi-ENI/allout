<?php

namespace App\Service;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class SortieService
{
    public function __construct(private readonly EntityManagerInterface $entityManager,
                                private readonly SortieRepository       $sortieRepository,
                                private readonly Security               $security,
                                private readonly ParticipantRepository  $participantRepository)
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

    public function inscription(int $id, Participant $participant): void
    {
        $sortie = $this->sortieRepository->find($id)
            ?? throw new NotFoundHttpException("Sortie introuvable.");

        if ($sortie->getEtat() !== Etat::Publiee) {
            throw new \DomainException("Les inscriptions ne sont pas ouvertes pour cette sortie.");
        }

        if ($sortie->getParticipants()->contains($participant)) {
            throw new \DomainException("Vous êtes déjà inscrit à cette sortie.");
        }

        if ($sortie->getParticipants()->count() >= $sortie->getNbInscriptionsMax()) {
            throw new \DomainException("Cette sortie affiche complet.");
        }

        $sortie->addParticipant($participant);
        $this->entityManager->flush();
    }

    public function desistement(int $id, Participant $participant): void
    {
        $sortie = $this->sortieRepository->find($id)
            ?? throw new NotFoundHttpException("Sortie introuvable.");

        if (!$sortie->getParticipants()->contains($participant)) {
            throw new \DomainException("Vous n'êtes pas inscrit à cette sortie.");
        }

        // La sortie ne doit pas avoir débuté — EnCours, Terminee, Archivee sont exclus
        $etat = $sortie->getEtat();
        if (in_array($etat, [Etat::EnCours, Etat::Terminee, Etat::Archivee, Etat::Annulee])) {
            throw new \DomainException("La sortie a déjà débuté, le désistement n'est plus possible.");
        }

        $sortie->removeParticipant($participant);
        $this->entityManager->flush();
    }
}
