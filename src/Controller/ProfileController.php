<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\PasswordChangeType;
use App\Service\ParticipantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller gérant l'affichage et les actions sur le profil d'un participant.
 *
 * Accessible uniquement aux utilisateurs connectés (IS_AUTHENTICATED_FULLY).
 */
#[Route('/profil', name: 'profile_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    /**
     * Affiche le profil d'un participant et gère le changement de mot de passe.
     *
     * Si un paramètre "id" est présent dans l'URL (/profil?id=42), on affiche
     * le profil du participant 42. Sinon, on affiche son propre profil.
     *
     * @param Request                     $request            La requête HTTP courante
     * @param UserPasswordHasherInterface $passwordHasher     Le service Symfony qui hash les mots de passe
     * @param ParticipantService          $participantService  Le service gérant la logique métier
     */
    #[Route('', name: 'show')]
    public function show(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        ParticipantService          $participantService,
    ): Response {
        /** @var Participant $currentUser */
        $currentUser = $this->getUser();

        $participant = $participantService->getParticipantOrCurrent(
            $request->query->get('id'),
            $currentUser
        );

        $isOwnProfile = $currentUser->getId() === $participant->getId();
        $isAdmin      = $this->isGranted('ROLE_ADMIN');

        $passwordForm = $this->createForm(PasswordChangeType::class);
        $passwordForm->handleRequest($request);

        if (($isOwnProfile || $isAdmin) && $passwordForm->isSubmitted() && $passwordForm->isValid()) {
            try {
                $participantService->changePassword(
                    $participant,
                    $passwordForm->get('currentPassword')->getData(),
                    $passwordForm->get('newPassword')->getData(),
                    $passwordHasher
                );
                $this->addFlash('success', 'Mot de passe modifié avec succès.');

                return $this->redirectToRoute('profile_show', ['id' => $participant->getId()]);
            } catch (\DomainException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('profile/show.html.twig', [
            'participant'   => $participant,
            'passwordForm'  => $passwordForm,
            'formSubmitted' => $passwordForm->isSubmitted(),
        ]);
    }
}
