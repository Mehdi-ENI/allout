<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des lieux.
 *
 * Gère l'affichage de la liste, la création, la modification et la suppression
 * des lieux. La route new conserve un support AJAX utilisé par le formulaire
 * imbriqué de création de sortie.
 */
#[Route('/lieu')]
final class LieuController extends AbstractController
{
    /**
     * Affiche la liste des lieux.
     *
     * La liste peut être filtrée par nom via le paramètre GET "recherche".
     *
     * @param Request        $request        La requête HTTP courante
     * @param LieuRepository $lieuRepository Le dépôt des lieux
     *
     * @return Response La vue de la liste des lieux
     */
    #[Route(name: 'app_lieu_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, LieuRepository $lieuRepository): Response
    {
        $recherche = $request->query->get('recherche');
        $lieux = $recherche ? $lieuRepository->findByNomContient($recherche) : $lieuRepository->findAll();

        return $this->render('lieu/index.html.twig', [
            'lieux' => $lieux,
            'recherche' => $recherche,
        ]);
    }

    /**
     * Crée un nouveau lieu.
     *
     * Cette route conserve un double comportement :
     * - Requête AJAX (XmlHttpRequest) : retourne le nouvel id et nom en JSON
     *   en cas de succès, ou les erreurs de validation avec un code 422.
     *   Utilisé par le formulaire imbriqué de création de sortie.
     * - Requête standard : redirige vers la liste avec un flash de succès,
     *   ou affiche le formulaire avec les erreurs de validation.
     *
     * @param Request                $request       La requête HTTP courante
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     *
     * @return Response|JsonResponse La vue du formulaire, une redirection ou une réponse JSON
     */
    #[Route('/new', name: 'app_lieu_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lieu = new Lieu();
        $form = $this->createForm(LieuType::class, $lieu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lieu);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'id'        => $lieu->getId(),
                        'nom'       => $lieu->getNom(),
                        'latitude'  => $lieu->getLatitude(),
                        'longitude' => $lieu->getLongitude(),
                    ]);
                }
            }

            $this->addFlash('success', 'Lieu ajouté avec succès.');
            return $this->redirectToRoute('app_lieu_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'errors' => (string) $form->getErrors(true, false),
            ], 422);
        }

        return $this->render('lieu/new.html.twig', [
            'lieu' => $lieu,
            'form' => $form,
        ]);
    }

    /**
     * Affiche et traite le formulaire de modification d'un lieu existant.
     *
     * En cas de succès redirige vers la liste avec un flash de succès.
     * En cas d'erreur de validation affiche le formulaire avec les erreurs
     * sous les champs via Symfony et ajoute les messages en flash.
     *
     * @param Request                $request       La requête HTTP courante
     * @param Lieu                   $lieu          Le lieu à modifier (résolu automatiquement par l'id)
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     *
     * @return Response La vue du formulaire ou une redirection
     */
    #[Route('/{id}/edit', name: 'app_lieu_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Lieu $lieu, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LieuType::class, $lieu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Lieu modifié avec succès.');
            return $this->redirectToRoute('app_lieu_index', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('lieu/edit.html.twig', [
            'lieu' => $lieu,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un lieu après vérification du token CSRF et des contraintes métier.
     *
     * La suppression est refusée si le lieu est rattaché à des sorties.
     * En cas d'erreur inattendue en base de données, une exception est interceptée
     * et un message d'erreur générique est affiché à l'utilisateur.
     * Si le token CSRF est invalide, la suppression est annulée immédiatement.
     *
     * @param Request                $request       La requête HTTP courante
     * @param Lieu                   $lieu          Le lieu à supprimer (résolu automatiquement par l'id)
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     *
     * @return Response Redirection vers la liste des lieux
     */
    #[Route('/{id}', name: 'app_lieu_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Lieu $lieu, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $lieu->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_lieu_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($lieu->getSorties()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce lieu : des sorties y sont associées.');
            return $this->redirectToRoute('app_lieu_index', [], Response::HTTP_SEE_OTHER);
        }

        try {
            $entityManager->remove($lieu);
            $entityManager->flush();
            $this->addFlash('success', 'Lieu supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
        }

        return $this->redirectToRoute('app_lieu_index', [], Response::HTTP_SEE_OTHER);
    }
}
