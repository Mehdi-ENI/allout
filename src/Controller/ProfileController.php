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

#[Route('/profil', name: 'profile_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    #[Route('', name: 'show')]
    public function show(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $participant = $this->getUser();

        $passwordForm = $this->createForm(PasswordChangeType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {

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
                return $this->redirectToRoute('profile_show');
            }
        }
        return $this->render('profile/show.html.twig', [
            'participant' => $participant,
            'passwordForm' => $passwordForm,
            'formSubmitted' => $passwordForm->isSubmitted(),
        ]);

    }
}
