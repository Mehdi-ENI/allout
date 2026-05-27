<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\UpdateParticipantType;
use App\Repository\ParticipantRepository;
use App\Service\ParticipantService;
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
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function update(
        int                $id,
        ParticipantService $participantService,
        Request            $request
    ): Response {
        $participant = $participantService->getParticipant($id);

        $isOwnProfile = $this->getUser()->getId() === $participant->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $participantForm = $this->createForm(UpdateParticipantType::class, $participant);
        $participantForm->handleRequest($request);

        if (($isOwnProfile || $isAdmin) && $participantForm->isSubmitted() && $participantForm->isValid()) {
            $imageFile = $participantForm->get('image')->getData();
            $participantService->updateParticipant($participant, $imageFile);

            $this->addFlash('success', $participant->getPrenom() . ' ' . $participant->getNom() . ' was updated !');
            return $this->redirectToRoute('profile_show', ['id' => $participant->getId()]);
        }

        return $this->render("participant/update.html.twig", [
            'updateForm'  => $participantForm,
            'participant' => $participant,
        ]);
    }
}
