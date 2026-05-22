<?php

namespace App\Controller;

use App\Form\PasswordChangeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ParticipantRepository;

#[Route('/profil', name: 'profile_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    #[Route('', name: 'show')]
    public function show(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ParticipantRepository $participantRepository,
    ): Response {
        $id = $request->query->get('id');
        $participant = $id
            ? $participantRepository->find($id)
            : $this->getUser();

        if (!$participant) {
            throw $this->createNotFoundException('Participant non trouvé');
        }

        // Le formulaire de mot de passe ne concerne que le profil connecté
        $currentUser = $this->getUser();
        $isOwnProfile = $currentUser->getId() === $participant->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $passwordForm = $this->createForm(PasswordChangeType::class);
        $passwordForm->handleRequest($request);

        if (($isOwnProfile || $isAdmin) && $passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();

            if (!$passwordHasher->isPasswordValid($participant, $currentPassword)) {
                $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');
            } else {
                $participant->setPassword(
                    $passwordHasher->hashPassword($participant, $newPassword)
                );
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe modifié avec succès.');
                return $this->redirectToRoute('profile_show', ['id' => $participant->getId()]);
            }
        }

        return $this->render('profile/show.html.twig', [
            'participant' => $participant,
            'passwordForm' => $passwordForm,
            'formSubmitted' => $passwordForm->isSubmitted(),
        ]);
    }
}
