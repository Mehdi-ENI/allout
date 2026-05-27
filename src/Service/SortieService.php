<?php

namespace App\Service;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service métier principal des sorties.
 *
 * Cette classe contient toutes les règles métier
 * liées à la gestion des sorties :
 * - création
 * - consultation
 * - publication
 * - inscription
 * - désistement
 * - annulation
 * - suppression
 *
 * Le controller ne doit contenir QUE :
 * - la gestion HTTP
 * - les formulaires
 * - les redirections
 *
 * Toute la logique métier doit vivre ici.
 */
class SortieService
{
    /**
     * Constructeur du service.
     *
     * @param EntityManagerInterface $entityManager Gestionnaire Doctrine
     * @param SortieRepository $sortieRepository Repository des sorties
     * @param MailService $mailService Service d'envoi des emails
     * @param SortieStateResolver $sortieStateResolver Service de calcul d'état
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SortieRepository $sortieRepository,
        private readonly MailService $mailService,
        private readonly SortieStateResolver $sortieStateResolver
    ) {
    }

    /**
     * Crée une nouvelle sortie.
     *
     * Cette méthode :
     * - persiste la sortie
     * - applique les règles métier futures
     *
     * @param Sortie $sortie Sortie à enregistrer
     */
    public function creerSortie(Sortie $sortie): void
    {
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }

    /**
     * Retourne le détail d'une sortie.
     * @param int $id Identifiant de la sortie
     * @return Sortie
     * @throws NotFoundHttpException Si la sortie n'existe pas
     */
    public function getSortieDetail(int $id): Sortie
    {
        $sortie = $this->sortieRepository->find($id);

        if (!$sortie) {
            throw new NotFoundHttpException('Sortie introuvable.');
        }

        return $sortie;
    }

    /**
     * Annule une sortie.
     *
     * Conditions :
     * - la sortie ne doit pas être :
     *   - en cours
     *   - terminée
     *   - archivée
     *   - déjà annulée
     *
     * @param Sortie $sortie Sortie à annuler
     * @param string $motif Motif d'annulation
     *
     * @throws \DomainException Si annulation impossible
     */
    public function annulerSortie(Sortie $sortie, string $motif): void {
        $etat = $this->sortieStateResolver->resolve($sortie);

        if (in_array($etat, [Etat::EnCours, Etat::Terminee, Etat::Archivee, Etat::Annulee])) {
            throw new \DomainException('Impossible d\'annuler cette sortie.');
        }

        $sortie->setAnnulee(true);
        $sortie->setMotifAnnulation($motif);

        $this->entityManager->flush();

        /*
         * Notification des participants.
         */
        $this->mailService->sendAnnulationSortie($sortie);
    }

    /**
     * Inscrit un participant à une sortie.
     *
     * Conditions :
     * - la sortie doit être publiée
     * - le participant ne doit pas être déjà inscrit
     * - la sortie ne doit pas être complète
     *
     * @param int $id Identifiant de la sortie
     * @param Participant $participant Participant connecté
     *
     * @throws NotFoundHttpException Si sortie introuvable
     * @throws \DomainException Si inscription impossible
     */
    public function inscription(int $id, Participant $participant): void {

        $sortie = $this->sortieRepository->find($id) ?? throw new NotFoundHttpException('Sortie introuvable.');
        $etat = $this->sortieStateResolver->resolve($sortie);

        if ($etat !== Etat::Publiee) {
            throw new \DomainException('Les inscriptions sont fermées.');
        }

        if ($sortie->getParticipants()->contains($participant)) {
            throw new \DomainException('Vous êtes déjà inscrit.');
        }

        if ($sortie->getParticipants()->count() >= $sortie->getNbInscriptionsMax()) {
            throw new \DomainException('La sortie est complète.');
        }

        $sortie->addParticipant($participant);
        $this->entityManager->flush();
    }

    /**
     * Désinscrit un participant d'une sortie.
     *
     * Conditions :
     * - le participant doit être inscrit
     * - la sortie ne doit pas être démarrée
     *
     * @param int $id Identifiant de la sortie
     * @param Participant $participant Participant connecté
     *
     * @throws NotFoundHttpException Si sortie introuvable
     * @throws \DomainException Si désistement impossible
     */
    public function desistement(int $id, Participant $participant): void {
        $sortie = $this->sortieRepository->find($id) ?? throw new NotFoundHttpException('Sortie introuvable.');

        if (!$sortie->getParticipants()->contains($participant)) {
            throw new \DomainException('Vous n\'êtes pas inscrit.');
        }

        $etat = $this->sortieStateResolver->resolve($sortie);

        if (in_array($etat, [Etat::EnCours, Etat::Terminee, Etat::Archivee, Etat::Annulee])) {
            throw new \DomainException('Le désistement n\'est plus possible.');
        }

        $sortie->removeParticipant($participant);

        $this->entityManager->flush();
    }

    /**
     * Publie une sortie.
     *
     * Conditions :
     * - seul l'organisateur peut publier
     * - la sortie doit être en état "Créée"
     *
     * @param int $id Identifiant de la sortie
     * @param Participant $participant Utilisateur connecté
     *
     * @throws NotFoundHttpException Si sortie introuvable
     * @throws \DomainException Si publication impossible
     */
    public function publication(int $id, Participant $participant): void {
        $sortie = $this->sortieRepository->find($id) ?? throw new NotFoundHttpException('Sortie introuvable.');

        if ($sortie->getOrganisateur()->getId() !== $participant->getId()) {
            throw new \DomainException('Vous n\'êtes pas organisateur.');
        }

        $etat = $this->sortieStateResolver->resolve($sortie);

        if ($etat !== Etat::Creee) {
            throw new \DomainException('La sortie ne peut pas être publiée.');
        }

        $sortie->setActive(true);
        $this->entityManager->flush();
    }

    /**
     * Supprime une sortie.
     *
     * Conditions :
     * - la sortie doit être en état "Créée"
     * - seul :
     *   - l'organisateur
     *   - OU un administrateur
     *   peut supprimer
     *
     * @param Sortie $sortie Sortie à supprimer
     * @param Participant $participant Utilisateur connecté
     *
     * @throws \DomainException Si suppression interdite
     */
    public function delete(Sortie $sortie, Participant $participant): void {
        $etat = $this->sortieStateResolver->resolve($sortie);

        if ($etat !== Etat::Creee) {
            throw new \DomainException('Seules les sorties en état "Créée" peuvent être supprimées.');
        }

        if ($sortie->getOrganisateur() !== $participant && !in_array('ROLE_ADMIN', $participant->getRoles())) {
            throw new \DomainException('Vous ne pouvez pas supprimer cette sortie.');
        }

        $this->entityManager->remove($sortie);

        $this->entityManager->flush();
    }
}
