<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ParticipantService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ParticipantRepository  $participantRepository,
        private readonly string                 $uploadDir
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
}
