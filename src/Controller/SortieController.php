<?php

namespace App\Controller;

use App\DTO\AnnulationDTO;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\AnnulationDTOType;
use App\Form\LieuType;
use App\Form\SortieType;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Service\MailService;
use App\Service\SortieService;
use App\Service\SortieStateResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des sorties.
 *
 * Gère :
 * - la création ;
 * - l'affichage ;
 * - la modification ;
 * - les inscriptions ;
 * - les désistements ;
 * - la publication ;
 * - l'annulation ;
 * - la suppression des sorties.
 */
#[Route('/sortie', name: 'sortie_')]
final class SortieController extends AbstractController
{
    /**
     *  Affiche et traite le formulaire de création d'une sortie.
     *  L'utilisateur doit être connecté pour accéder à cette page.
     *
     *  Fonctionnement :
     *  - crée une nouvelle entité Sortie,
     *  - construit le formulaire associé,
     *  - valide les données envoyées,
     *  - associe l'utilisateur connecté comme organisateur,
     *  - délègue la sauvegarde au service métier.
     *
     *  Un formulaire secondaire permet également d'ajouter un lieu
     *  directement depuis une modale Bootstrap.
     *
     * @param Request $request - Contient la requête HTTP et les données du formulaire.
     * @param SortieService $sortieService - Service métier chargé de la création de la sortie.
     * @return Response - Retourne :
     *                      - la page du formulaire,
     *                      - ou une redirection vers le détail de la sortie.
     */
    #[Route('/create', name: 'create')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request, SortieService $sortieService): Response
    {

        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);

        /**
        * Formulaire utilisé dans la modale d'ajout rapide d'un lieu.
        */
        $lieu = new Lieu();
        $lieuForm = $this->createForm(LieuType::class, $lieu);

        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $sortie->setOrganisateur($this->getUser());

            try {

                $sortieService->creerSortie($sortie);
                $this->addFlash('success', 'Sortie créée avec succès');

                return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);

            } catch (\Exception $e) {

                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->render('sortie/create.html.twig', [
            'sortieForm' => $sortieForm->createView(),
            'lieuForm' => $lieuForm->createView(),
        ]);
    }

    /**
     * Affiche la liste des sorties avec filtres.
     * Cette page permet :
     * - d'afficher les sorties,
     * - d'appliquer des filtres de recherche,
     * - d'afficher le site par défaut de l'utilisateur connecté.
     *
     * Les états des sorties sont calculés via le SortieStateResolver afin d'éviter toute logique métier dans Twig.
     *
     * @param SortieRepository $sortieRepository - Repository utilisé pour récupérer les sorties filtrées.
     *
     * @param SiteRepository $siteRepository - Repository utilisé pour récupérer la liste des sites.
     * @param SortieStateResolver $sortieStateResolver - Service chargé de calculer l'état métier d'une sortie.
     * @param Request $request - Requête HTTP contenant les filtres éventuels.
     * @return Response - Retourne la page contenant la liste des sorties.
     */
    #[Route('/list', name: 'list')]
    public function list(SortieRepository $sortieRepository,
                         SiteRepository   $siteRepository,
                         SortieStateResolver $sortieStateResolver,
                         Request          $request): Response{
        $filters = $request->query->all();

        /*
         * Premier chargement :
         * on applique automatiquement le site
         * de l'utilisateur connecté.
         */
        if (!$request->query->has('site') && $this->getUser()) {
            /** @var Participant $user */
            $user = $this->getUser();
            $filters['site'] = $user->getSite()?->getId();
        }

        $sorties = $sortieRepository->findWithFilters($filters);

        /*
         * Préparation des données pour Twig.
         * Chaque sortie est accompagnée de son état calculé.
         */
        $sortiesAvecEtat = [];

        foreach ($sorties as $sortie) {

            $sortiesAvecEtat[] = [
                'sortie' => $sortie,
                'etat' => $sortieStateResolver->resolve($sortie),
            ];
        }

        $sites = $siteRepository->findAll();

        return $this->render('sortie/list.html.twig', [
            'sorties' => $sortiesAvecEtat,
            'sites' => $sites
        ]);
    }

    /**
     * Affiche le détail d'une sortie.
     * @param SortieService $sortieService - Service métier utilisé pour récupérer une sortie.
     * @param SortieStateResolver $sortieStateResolver - Service chargé de calculer l'état métier de la sortie.
     * @param int $id -  Identifiant de la sortie.
     * @return Response - Retourne :
     *                      - la page de détail,
     *                      - ou une redirection si la sortie n'existe pas.
     */
    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(SortieService $sortieService, SortieStateResolver $sortieStateResolver, int $id): Response
    {
        try {
            $sortie = $sortieService->getSortieDetail($id);
            $etat = $sortieStateResolver->resolve($sortie);

            return $this->render('sortie/detail.html.twig', [
                'sortie' => $sortie,
                'etat' => $etat,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('sortie_list');
    }

    /**
     * Permet à l'organisateur de modifier les informations d'une sortie existante.
     *
     * @param int $id -  Identifiant de la sortie à modifier.
     * @param Request $request - Contient les données du formulaire.
     * @param SortieService $sortieService - Service métier utilisé pour récupérer la sortie.
     * @param EntityManagerInterface $entityManager - Gestionnaire Doctrine utilisé pour sauvegarder les modifications.
     * @return Response - Retourne :
     *                      - le formulaire,
     *                      - ou une redirection après sauvegarde.
     */
    #[Route('/{id}/update', name: 'update', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function update(int                    $id,
                           Request                $request,
                           SortieService          $sortieService,
                           SortieStateResolver    $sortieStateResolver,
                           EntityManagerInterface $entityManager): Response
    {
        $sortie = $sortieService->getSortieDetail($id);
        $etat = $sortieStateResolver->resolve($sortie);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $entityManager->flush();
                $this->addFlash('success', 'Sortie mise à jour avec succès');
                return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->render('sortie/update.html.twig', [
            'sortieForm' => $form,
            'sortie' => $sortie,
            'etat' => $etat,
        ]);
    }

    /**
     * Permet d'annuler une sortie.
     * L'utilisateur doit être connecté.
     *
     * Une sortie annulée :
     * - change d'état,
     * - possède un motif d'annulation,
     * - déclenche l'envoi d'e-mails aux participants.
     *
     * @param Sortie $sortie - Sortie automatiquement récupérée par Symfony.
     * @param Request $request - Contient les données du formulaire d'annulation.
     * @param SortieService $sortieService - Service métier chargé de l'annulation.
     * @return Response - Retourne :
     *                      - le formulaire d'annulation,
     *                      - ou une redirection après validation.
     */
    #[Route('/{id}/annuler', name: 'annuler', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function annuler(Sortie $sortie, Request $request, SortieService $sortieService): Response
    {
        $dto = new AnnulationDTO();
        $form = $this->createForm(AnnulationDTOType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $sortieService->annulerSortie($sortie, $dto->motif);
                $this->addFlash('success', 'La sortie a été annulée.');
                return $this->redirectToRoute('sortie_list');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('sortie/annuler.html.twig', [
            'sortie' => $sortie,
            'form' => $form
        ]);
    }

    /**
     * Inscrit un participant à une sortie.
     * Vérifie :
     * - le token CSRF,
     * - l'état de la sortie,
     * - les règles métier d'inscription.
     *
     * @param Request $request - Contient le token CSRF envoyé par le formulaire.
     * @param SortieService $sortieService - Service métier chargé de gérer l'inscription.
     * @param int $id - Identifiant de la sortie.
     * @return Response - Redirige vers la liste des sorties.
     */
    #[Route('/{id}/inscription', name: 'inscription', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function inscription(Request $request, SortieService $sortieService, int $id): Response
    {
        if (!$this->isCsrfTokenValid('inscription' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('sortie_list');
        }

        /** @var Participant $participant */
        $participant = $this->getUser();

        try {
            $sortieService->inscription($id, $participant);
            $this->addFlash('success', 'Inscription confirmée.');
        } catch (NotFoundHttpException|\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('sortie_list');
    }

    /**
     * Désinscrit un participant d'une sortie.
     * Vérifie :
     * - le token CSRF,
     * - que le participant est inscrit,
     * - que la sortie autorise encore le désistement.
     *
     * @param Request $request - Contient le token CSRF du formulaire.
     * @param SortieService $sortieService - Service métier chargé du désistement.
     * @param int $id - Identifiant de la sortie.
     * @return Response
     */
    #[Route('/{id}/desistement', name: 'desistement', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function desistement(Request $request, SortieService $sortieService, int $id): Response
    {
        if (!$this->isCsrfTokenValid('desistement' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('sortie_list');
        }

        /** @var Participant $participant */
        $participant = $this->getUser();

        try {
            $sortieService->desistement($id, $participant);
            $this->addFlash('success', 'Désistement pris en compte.');
        } catch (NotFoundHttpException|\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('sortie_list');
    }

    /**
     * Publie une sortie.
     * Une sortie publiée devient visible et ouverte aux inscriptions.
     * Seul l'organisateur peut publier sa sortie.
     *
     * @param Request $request - Contient le token CSRF.
     * @param SortieService $sortieService - Service métier chargé de la publication.
     * @param int $id - Identifiant de la sortie.
     * @return Response - Redirection vers la liste des sorties.
     */
    #[Route('/{id}/publication', name: 'publication', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function publication(Request $request, SortieService $sortieService, int $id): Response
    {
        if (!$this->isCsrfTokenValid('publication' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('sortie_list');
        }

        /** @var Participant $participant */
        $participant = $this->getUser();

        try {
            $sortieService->publication($id, $participant);
            $this->addFlash('success', 'Publication pris en compte.');
        } catch (NotFoundHttpException|\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('sortie_list');
    }

    /**
     * Supprime une sortie.
     * Conditions :
     * - sortie en état "Créée"
     * - utilisateur organisateur ou administrateur
     *
     * @param Request $request - Contient le token CSRF.
     * @param SortieService $sortieService - Service métier chargé de la suppression.
     * @return Response - Redirection vers la liste des sorties.
     */
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id, Request $request, SortieService $sortieService): Response
    {

        if (!$this->isCsrfTokenValid('suppression' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('sortie_list');
        }

        /** @var Participant $participant */
        $participant = $this->getUser();

        try {
            $sortie = $sortieService->getSortieDetail($id);
            $sortieService->delete($sortie, $participant);
            $this->addFlash('success', 'Sortie supprimée.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('sortie_list');
    }
}
