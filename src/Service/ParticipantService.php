<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class ParticipantService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParticipantRepository  $participantRepository,
        private string                 $uploadDir
    ) {}

    public function getParticipant(int $id): Participant
    {
        return $this->participantRepository->find($id)
            ?? throw new NotFoundHttpException('Participant not found');
    }

    public function updateParticipant(Participant $participant, ?object $imageFile): void
    {
        if ($imageFile) {
            $this->handleImageUpload($participant, $imageFile);
        }

        $this->entityManager->persist($participant);
        $this->entityManager->flush();
    }

    private function handleImageUpload(Participant $participant, object $imageFile): void
    {
        if ($participant->getImage()) {
            $oldFile = $this->uploadDir . '/' . $participant->getImage();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        $safeName = preg_replace('/[^a-z0-9]+/i', '-', $participant->getPseudo() ?? $participant->getEmail());
        $newFileName = $safeName . '-' . uniqid() . '.' . $imageFile->guessExtension();
        $imageFile->move($this->uploadDir, $newFileName);
        $participant->setImage($newFileName);
    }

    public function getParticipantOrCurrent(mixed $id, Participant $currentUser): Participant
    {
        if ($id) {
            return $this->participantRepository->find($id)
                ?? throw new NotFoundHttpException('Participant non trouvé');
        }
        return $currentUser;
    }

    public function changePassword(Participant $participant, string $currentPassword, string $newPassword, UserPasswordHasherInterface $passwordHasher): void
    {
        if (!$passwordHasher->isPasswordValid($participant, $currentPassword)) {
            throw new \DomainException('Le mot de passe actuel est incorrect.');
        }

        $participant->setPassword(
            $passwordHasher->hashPassword($participant, $newPassword)
        );

        $this->entityManager->flush();
    }
}
