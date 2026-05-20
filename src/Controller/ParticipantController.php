<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/participant', name: 'participant_')]
final class ParticipantController extends AbstractController
{
    #[Route('/list', name: 'list')]
    #[IsGranted("ROLE_ADMIN")]
    public function list(ParticipantRepository $participantRepository): Response
    {
        $participants = $participantRepository->findAll();
        return $this->render('participant/list.html.twig', ['participants' => $participants]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '[0-9]+'])]
    public function detail(int $id, ParticipantRepository $participantRepository): Response
    {
        $participant = $participantRepository->find($id);

        if (!$participant) {
            throw $this->createNotFoundException('Ooops ! Not found');
        }

        return $this->render('participant/detail.html.twig', [
            'participant' => $participant
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id'=>'\d+'])]
    public function delete(EntityManagerInterface $entityManager, Participant $participant ): Response
    {
        $entityManager->remove($participant);
        $entityManager->flush();

        $this->addFlash('success', 'Participant sucessfully deleted !');
        return $this->redirectToRoute('participant_list');
    }

}
