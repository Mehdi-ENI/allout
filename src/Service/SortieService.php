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


class SortieService
{
    public function __construct(private readonly EntityManagerInterface $entityManager,
                                private readonly SortieRepository       $sortieRepository,
                                private readonly MailService            $mailService)
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

    public function annulerSortie(Sortie $sortie, string $motif): void
    {
        $etat = $sortie->getEtat();
        if (in_array($etat, [Etat::EnCours, Etat::Terminee, Etat::Archivee, Etat::Annulee])) {
            throw new \DomainException('Impossible d\'annuler une sortie déjà commencée, terminée ou annulée.');
        }
        $sortie->setAnnulee(true);
        $sortie->setMotifAnnulation($motif);
        $this->entityManager->flush();
        $this->mailService->sendAnnulationSortie($sortie);
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

    public function publication(int $id, Participant $participant): void
    {
        $sortie = $this->sortieRepository->find($id)
            ?? throw new NotFoundHttpException("Sortie introuvable.");

        if ($sortie->getOrganisateur()->getId() != $participant->getId()) {
            throw new \DomainException("Vous n'êtes pas l'organisateur de la sortie");
        }

        if ($sortie->getEtat() !== Etat::Creee) {
            throw new \DomainException("La sortie ne peut pas être publiée dans son état actuel.");
        }

        $sortie->setActive(true);
        $this->entityManager->flush();
    }

    public function delete(Sortie $sortie, Participant $participant): void
    {
        if ($sortie->getEtat() !== Etat::Creee) {
            throw new \DomainException('Seules les sorties en état "Créée" peuvent être supprimées.');
        }

        if (
            $sortie->getOrganisateur() !== $participant
            && !in_array('ROLE_ADMIN', $participant->getRoles())
        ) {
            throw new \DomainException('Vous ne pouvez pas supprimer cette sortie.');
        }

        $this->entityManager->remove($sortie);
        $this->entityManager->flush();
    }
}
