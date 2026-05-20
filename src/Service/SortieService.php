<?php

namespace App\Service;

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

    public function inscription(int $id): void
    {
        [$sortie, $participant] = $this->resolveContext($id);

        if ($sortie->getEtat() !== Etat::Publiee || $sortie->getParticipants()->contains($participant)) {
            throw new \Exception("Inscription non autorisée pour cette sortie.");
        }

        $sortie->addParticipant($participant);
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }

    public function desistement(int $id): void
    {
        [$sortie, $participant] = $this->resolveContext($id);

        if ($sortie->getEtat() !== Etat::Publiee || !$sortie->getParticipants()->contains($participant)) {
            throw new \Exception("Désistement non autorisé pour cette sortie.");
        }

        $sortie->removeParticipant($participant);
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }

    private function resolveContext(int $id): array
    {
        $sortie = $this->sortieRepository->find($id);
        if (!$sortie) {
            throw new NotFoundHttpException("Sortie introuvable.");
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw new AccessDeniedException("Vous devez être connecté.");
        }

        $participant = $this->participantRepository->findOneBy(['email' => $currentUser->getUserIdentifier()]);
        if (!$participant) {
            throw new NotFoundHttpException("Participant introuvable.");
        }

        return [$sortie, $participant];
    }
}
