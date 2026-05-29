<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\RegistrationFormType;
use App\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    #[IsGranted("ROLE_ADMIN")]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        FileUploader $fileUploader,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new Participant();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Hashage du mot de passe
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Upload de l'image si fournie
            $file = $form->get('image')->getData();
            if ($file) {
                $user->setImage(
                    $fileUploader->upload($file, $this->getParameter('profile_image_dir'), $user->getPseudo())
                );
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Le compte de ' . $user->getPrenom() . ' ' . $user->getNom() . ' a bien été créé !');
            return $this->redirectToRoute('participant_list');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
