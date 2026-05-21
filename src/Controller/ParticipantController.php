<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\UpdateParticipantType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    #[IsGranted("ROLE_ADMIN")]
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
    #[IsGranted("ROLE_ADMIN")]
    public function delete(EntityManagerInterface $entityManager, Participant $participant ): Response
    {
        $entityManager->remove($participant);
        $entityManager->flush();

        $this->addFlash('success', 'Participant sucessfully deleted !');
        return $this->redirectToRoute('participant_list');
    }

    #[Route('/{id}/desactivate', name: 'desactivate')]
    #[IsGranted("ROLE_ADMIN")]
    public function desactivate(ParticipantRepository $participantRepository, Participant $participant): Response
    {
        $idParticipant = $participant->getId();
        $participantRepository->desactivateParticipant($idParticipant);
        return $this->redirectToRoute('participant_list');
    }

    #[Route('/{id}/activate', name: 'activate')]
    #[IsGranted("ROLE_ADMIN")]
    public function activate(ParticipantRepository $participantRepository, Participant $participant): Response
    {
        $idParticipant = $participant->getId();
        $participantRepository->activateParticipant($idParticipant);
        return $this->redirectToRoute('participant_list');
    }

    #[Route('/{id}/update', name: 'update', methods: ["GET", "POST"])]
    #[IsGranted("ROLE_ADMIN")]
    public function update(int                    $id,
                           ParticipantRepository        $participantRepository,
                           Request                $request,
                           EntityManagerInterface $entityManager): Response
    {
        $participant = $participantRepository->find($id);
        $participantForm = $this->createForm(UpdateParticipantType::class, $participant);

        $participantForm->handleRequest($request);

        if ($participantForm->isSubmitted() && $participantForm->isValid()) {


            $entityManager->persist($participant);
            $entityManager->flush();
            $this->addFlash('success', $participant->getPrenom() .' '. $participant->getnom(). ' was updated !');
            return $this->redirectToRoute('participant_detail', ['id' => $participant->getId()]);
        }

        return $this->render("participant/update.html.twig", [
            'updateForm' => $participantForm
        ]);
    }
}
