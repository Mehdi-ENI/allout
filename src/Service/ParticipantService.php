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
     * On injecte les dépendances dont le service a besoin pour travailler.
     *
     * @param EntityManagerInterface $entityManager  Permet de sauvegarder en base de données
     * @param ParticipantRepository  $participantRepository Permet de chercher des participants en BDD
     * @param string                 $uploadDir      Dossier où sont stockées les images de profil
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
     * @return Participant Le participant trouvé
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
     * Exemple : sur une page de profil, si on visite /profil sans paramètre,
     * on affiche son propre profil. Si on visite /profil?id=42, on affiche celui du participant 42.
     *
     * @param int|string|null $id          L'identifiant du participant (peut être null)
     * @param Participant     $currentUser L'utilisateur actuellement connecté
     *
     * @return Participant Le participant trouvé ou l'utilisateur courant
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
     * Met à jour les informations d'un participant, avec ou sans nouvelle image de profil.
     *
     * @param Participant      $participant Le participant à mettre à jour
     * @param UploadedFile|null $imageFile  Le fichier image uploadé, ou null si pas de changement
     *
     * @throws \RuntimeException Si le déplacement du fichier image échoue
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
     * C'est comme changer la serrure d'une porte : on vérifie d'abord
     * que vous avez la bonne clé actuelle avant d'en poser une nouvelle.
     *
     * @param Participant                 $participant      Le participant dont on change le mot de passe
     * @param string                      $currentPassword  Le mot de passe actuel (en clair)
     * @param string                      $newPassword      Le nouveau mot de passe (en clair)
     * @param UserPasswordHasherInterface $passwordHasher   Le service Symfony qui hash les mots de passe
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
     * Gère l'upload d'une image de profil : supprime l'ancienne si elle existe,
     * génère un nom de fichier unique et déplace le fichier dans le bon dossier.
     *
     * C'est comme changer la photo dans un cadre : on retire l'ancienne,
     * on coupe la nouvelle à la bonne taille, et on la met en place.
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
     * Le uniqid() garantit qu'on n'écrase jamais un fichier existant.
     *
     * @param Participant  $participant Le participant concerné (pour nommer le fichier)
     * @param UploadedFile $imageFile   Le fichier uploadé (pour récupérer l'extension)
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
