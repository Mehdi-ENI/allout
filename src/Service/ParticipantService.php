<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service gérant toutes les opérations métier liées aux participants.
 *
 * Un "service métier", c'est comme un cuisinier dans un restaurant :
 * le controller (le serveur) prend la commande et l'apporte en cuisine,
 * mais c'est le cuisinier qui fait le vrai travail.
 */
readonly class ParticipantService
{
    /**
     * @param EntityManagerInterface $entityManager        Permet de sauvegarder en base de données
     * @param ParticipantRepository  $participantRepository Permet de chercher des participants en BDD
     * @param string                 $uploadDir             Dossier où sont stockées les images de profil
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParticipantRepository  $participantRepository,
        private string                 $uploadDir
    ) {}

    /**
     * Récupère un participant par son identifiant.
     *
     * @param int $id L'identifiant du participant recherché
     *
     * @throws NotFoundHttpException Si aucun participant ne correspond à cet id
     */
    public function getParticipant(int $id): Participant
    {
        return $this->participantRepository->find($id)
            ?? throw new NotFoundHttpException('Participant not found');
    }

    /**
     * Récupère un participant par son id, ou retourne l'utilisateur connecté si aucun id n'est fourni.
     *
     * @param int|string|null $id          L'identifiant du participant (peut être null)
     * @param Participant     $currentUser L'utilisateur actuellement connecté
     *
     * @throws NotFoundHttpException Si un id est fourni mais ne correspond à aucun participant
     */
    public function getParticipantOrCurrent(int|string|null $id, Participant $currentUser): Participant
    {
        if ($id === null) {
            return $currentUser;
        }

        return $this->participantRepository->find((int) $id)
            ?? throw new NotFoundHttpException('Participant non trouvé');
    }

    /**
     * Supprime ou anonymise un participant selon ses liaisons avec les sorties.
     *
     * Deux cas possibles :
     * - Le participant n'est lié à aucune sortie (ni organisateur, ni inscrit)
     *   → suppression physique en base de données.
     * - Le participant est lié à au moins une sortie
     *   → anonymisation : les données personnelles sont effacées mais l'entité
     *   est conservée pour ne pas casser les relations existantes.
     *
     * Les champs uniques (email, pseudo) sont suffixés avec l'id du participant
     * pour éviter les conflits de contraintes d'unicité.
     *
     * @param Participant $participant Le participant à supprimer ou anonymiser
     */
    public function delete(Participant $participant): void
    {
        if ($this->isLinkedToSortie($participant)) {
            $this->anonymize($participant);
        } else {
            $this->entityManager->remove($participant);
        }

        $this->entityManager->flush();
    }

    /**
     * Met à jour les informations d'un participant, avec ou sans nouvelle image de profil.
     *
     * @param Participant       $participant Le participant à mettre à jour
     * @param UploadedFile|null $imageFile   Le fichier image uploadé, ou null si pas de changement
     */
    public function updateParticipant(Participant $participant, ?UploadedFile $imageFile): void
    {
        if ($imageFile !== null) {
            $this->handleImageUpload($participant, $imageFile);
        }

        $this->entityManager->persist($participant);
        $this->entityManager->flush();
    }

    /**
     * Change le mot de passe d'un participant après vérification de l'ancien.
     *
     * @param Participant                 $participant     Le participant dont on change le mot de passe
     * @param string                      $currentPassword Le mot de passe actuel (en clair)
     * @param string                      $newPassword     Le nouveau mot de passe (en clair)
     * @param UserPasswordHasherInterface $passwordHasher  Le service Symfony qui hash les mots de passe
     *
     * @throws \DomainException Si le mot de passe actuel est incorrect
     */
    public function changePassword(
        Participant                 $participant,
        string                      $currentPassword,
        string                      $newPassword,
        UserPasswordHasherInterface $passwordHasher
    ): void {
        if (!$passwordHasher->isPasswordValid($participant, $currentPassword)) {
            throw new \DomainException('Le mot de passe actuel est incorrect.');
        }

        $participant->setPassword(
            $passwordHasher->hashPassword($participant, $newPassword)
        );

        $this->entityManager->flush();
    }

    /**
     * Vérifie si un participant est lié à au moins une sortie,
     * que ce soit en tant qu'organisateur ou en tant qu'inscrit.
     *
     * @param Participant $participant Le participant à vérifier
     *
     * @return bool True si le participant est lié à une sortie, false sinon
     */
    public function isLinkedToSortie(Participant $participant): bool
    {
        return $participant->getSortiesOrganisees()->count() > 0
            || $participant->getSortiesParticipees()->count() > 0;
    }

    /**
     * Anonymise un participant en effaçant toutes ses données personnelles.
     *
     * Les champs soumis à une contrainte d'unicité (email, pseudo) sont
     * suffixés avec l'id du participant pour éviter les conflits en base.
     *
     * Exemple : email "jean@mail.com" devient "deleted-42@deleted.invalid"
     *
     * @param Participant $participant Le participant à anonymiser
     */
    private function anonymize(Participant $participant): void
    {
        $id = $participant->getId();

        $participant
            ->setEmail('deleted-' . $id . '@deleted.invalid')
            ->setPseudo('deleted-' . $id)
            ->setNom(null)
            ->setPrenom(null)
            ->setTelephone(null)
            ->setImage(null)
            ->setActif(false)
            ->setRoles([]);
    }

    /**
     * Gère l'upload d'une image de profil.
     *
     * @param Participant  $participant Le participant concerné
     * @param UploadedFile $imageFile   Le fichier image uploadé
     */
    private function handleImageUpload(Participant $participant, UploadedFile $imageFile): void
    {
        $this->deleteOldImage($participant);

        $newFileName = $this->generateFileName($participant, $imageFile);
        $imageFile->move($this->uploadDir, $newFileName);
        $participant->setImage($newFileName);
    }

    /**
     * Supprime l'ancienne image de profil du disque si elle existe.
     *
     * @param Participant $participant Le participant dont on supprime l'image
     */
    private function deleteOldImage(Participant $participant): void
    {
        if ($participant->getImage() === null) {
            return;
        }

        $oldFile = $this->uploadDir . '/' . $participant->getImage();
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    /**
     * Génère un nom de fichier unique et sécurisé pour une image de profil.
     *
     * Exemple : "jean-dupont-64f3a2b1c4e5f.jpg"
     *
     * @param Participant  $participant Le participant concerné
     * @param UploadedFile $imageFile   Le fichier uploadé
     *
     * @return string Le nom de fichier généré
     */
    private function generateFileName(Participant $participant, UploadedFile $imageFile): string
    {
        $baseName = $participant->getPseudo() ?? $participant->getEmail();
        $safeName = preg_replace('/[^a-z0-9]+/i', '-', $baseName);

        return strtolower($safeName) . '-' . uniqid() . '.' . $imageFile->guessExtension();
    }
}
