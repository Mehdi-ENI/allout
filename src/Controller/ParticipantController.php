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

/**
 * Gère les actions d'administration sur les participants.
 *
 * Toutes les routes sont préfixées par /participant.
 * Les actions d'administration (list, delete, activate, desactivate)
 * sont réservées aux administrateurs via ROLE_ADMIN.
 */
#[Route('/participant', name: 'participant_')]
final class ParticipantController extends AbstractController
{
    /**
     * Affiche la liste de tous les participants.
     */
    #[Route('/list', name: 'list')]
    #[IsGranted("ROLE_ADMIN")]
    public function list(ParticipantRepository $participantRepository): Response
    {
        $participants = $participantRepository->findAll();

        return $this->render('participant/list.html.twig', [
            'participants' => $participants,
        ]);
    }

    /**
     * Supprime ou anonymise un participant selon ses liaisons avec les sorties.
     *
     * - Aucune liaison → suppression physique en base
     * - Liaison existante → anonymisation des données personnelles
     *
     * @param Participant        $participant       Résolu automatiquement par Symfony via l'id dans l'URL
     * @param ParticipantService $participantService Gère la logique métier de suppression
     */
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'])]
    #[IsGranted("ROLE_ADMIN")]
    public function delete(Participant $participant, ParticipantService $participantService): Response
    {
        $participantService->delete($participant);

        $this->addFlash('success', 'Participant successfully deleted!');

        return $this->redirectToRoute('participant_list');
    }

    /**
     * Désactive le compte d'un participant.
     * Un compte désactivé ne peut plus se connecter.
     *
     * @param Participant           $participant          Résolu automatiquement par Symfony via l'id dans l'URL
     * @param ParticipantRepository $participantRepository
     */
    #[Route('/{id}/desactivate', name: 'desactivate', requirements: ['id' => '\d+'])]
    #[IsGranted("ROLE_ADMIN")]
    public function desactivate(ParticipantRepository $participantRepository, Participant $participant): Response
    {
        $participantRepository->desactivateParticipant($participant->getId());

        $this->addFlash('success', 'Participant successfully desactivated!');

        return $this->redirectToRoute('participant_list');
    }

    /**
     * Réactive le compte d'un participant.
     *
     * @param Participant           $participant          Résolu automatiquement par Symfony via l'id dans l'URL
     * @param ParticipantRepository $participantRepository
     */
    #[Route('/{id}/activate', name: 'activate', requirements: ['id' => '\d+'])]
    #[IsGranted("ROLE_ADMIN")]
    public function activate(ParticipantRepository $participantRepository, Participant $participant): Response
    {
        $participantRepository->activateParticipant($participant->getId());

        $this->addFlash('success', 'Participant successfully activated!');

        return $this->redirectToRoute('participant_list');
    }

    /**
     * Affiche et traite le formulaire de modification d'un participant.
     *
     * Seul le participant concerné ou un administrateur peut modifier un profil.
     * Si l'utilisateur n'est ni le propriétaire du profil ni admin, le formulaire
     * est affiché mais la soumission est ignorée.
     *
     * @param int                $id                 L'identifiant du participant à modifier
     * @param ParticipantService $participantService  Gère la logique métier (récupération, mise à jour)
     * @param Request            $request             La requête HTTP courante
     */
    #[Route('/{id}/update', name: 'update', methods: ["GET", "POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function update(
        int                $id,
        ParticipantService $participantService,
        Request            $request
    ): Response {
        $participant = $participantService->getParticipant($id);

        /** @var Participant $currentUser */
        $currentUser  = $this->getUser();
        $isOwnProfile = $currentUser->getId() === $participant->getId();
        $isAdmin      = $this->isGranted('ROLE_ADMIN');

        $participantForm = $this->createForm(UpdateParticipantType::class, $participant);
        $participantForm->handleRequest($request);

        if (($isOwnProfile || $isAdmin) && $participantForm->isSubmitted() && $participantForm->isValid()) {
            $imageFile = $participantForm->get('image')->getData();
            $participantService->updateParticipant($participant, $imageFile);

            $this->addFlash('success', $participant->getPrenom() . ' ' . $participant->getNom() . ' was updated!');

            return $this->redirectToRoute('profile_show', ['id' => $participant->getId()]);
        }

        return $this->render("participant/update.html.twig", [
            'updateForm'  => $participantForm,
            'participant' => $participant,
        ]);
    }
}
