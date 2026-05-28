<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des sites.
 *
 * Gère l'affichage, l'ajout, la modification et la suppression des sites
 * directement depuis une interface inline sans pages séparées.
 */
#[Route('/site')]
#[IsGranted('ROLE_ADMIN')]
final class SiteController extends AbstractController
{
    /**
     * Affiche la liste des sites avec les formulaires d'ajout et d'édition inline.
     *
     * Gère également la soumission du formulaire d'ajout via POST.
     * La liste peut être filtrée par nom via le paramètre GET "recherche".
     *
     * @param Request                $request        La requête HTTP courante
     * @param SiteRepository         $siteRepository Le dépôt des sites
     * @param EntityManagerInterface $entityManager  Le gestionnaire d'entités Doctrine
     *
     * @return Response La vue de la liste des sites
     */
    #[Route(name: 'app_site_index', methods: ['GET', 'POST'])]
    public function index(Request $request, SiteRepository $siteRepository, EntityManagerInterface $entityManager): Response
    {
        $recherche = $request->query->get('recherche');
        $sites = $recherche ? $siteRepository->findByNomContient($recherche) : $siteRepository->findAll();

        $nouveauSite = new Site();
        $formNew = $this->createForm(SiteType::class, $nouveauSite, [
            'action' => $this->generateUrl('app_site_index'),
            'method' => 'POST',
        ]);
        $formNew->handleRequest($request);

        if ($formNew->isSubmitted() && $formNew->isValid()) {
            $entityManager->persist($nouveauSite);
            $entityManager->flush();
            $this->addFlash('success', 'Site ajouté avec succès.');
            return $this->redirectToRoute('app_site_index', [], Response::HTTP_SEE_OTHER);
        } elseif ($formNew->isSubmitted()) {
            foreach ($formNew->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('site/index.html.twig', [
            'sites' => $sites,
            'recherche' => $recherche,
            'formNew' => $formNew->createView(),
            'formsEdit' => $this->buildFormsEdit($sites),
        ]);
    }

    /**
     * Traite la modification d'un site existant.
     *
     * Reçoit uniquement les requêtes POST depuis le formulaire d'édition inline.
     * Redirige toujours vers la liste, avec un flash de succès ou d'erreur selon le résultat.
     *
     * @param Request                $request       La requête HTTP courante
     * @param Site                   $site          Le site à modifier (résolu automatiquement par l'id)
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     *
     * @return Response Redirection vers la liste des sites
     */
    #[Route('/{id}/edit', name: 'app_site_edit', methods: ['POST'])]
    public function edit(Request $request, Site $site, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Site modifié avec succès.');
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_site_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Supprime un site après vérification du token CSRF et des contraintes métier.
     *
     * La suppression est refusée si le site est rattaché à des sorties ou à des participants.
     * En cas d'erreur inattendue en base de données, une exception est interceptée
     * et un message d'erreur générique est affiché à l'utilisateur.
     *
     * @param Request                $request       La requête HTTP courante
     * @param Site                   $site          Le site à supprimer (résolu automatiquement par l'id)
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     *
     * @return Response Redirection vers la liste des sites
     */
    #[Route('/{id}', name: 'app_site_delete', methods: ['POST'])]
    public function delete(Request $request, Site $site, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $site->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_site_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($site->getSorties()->count() > 0 || $site->getParticipants()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce site : des sorties ou des participants y sont rattachés.');
            return $this->redirectToRoute('app_site_index', [], Response::HTTP_SEE_OTHER);
        }

        try {
            $entityManager->remove($site);
            $entityManager->flush();
            $this->addFlash('success', 'Site supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
        }

        return $this->redirectToRoute('app_site_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Construit les formulaires d'édition pour chaque site de la liste.
     *
     * Chaque formulaire est configuré avec l'action pointant vers la route d'édition
     * du site correspondant. Les vues sont générées directement pour être passées au template.
     *
     * @param Site[] $sites La liste des sites pour lesquels générer un formulaire
     *
     * @return array<int, FormView> Un tableau indexé par l'id du site contenant les vues de formulaire
     */
    private function buildFormsEdit(array $sites): array
    {
        $formsEdit = [];
        foreach ($sites as $site) {
            $formsEdit[$site->getId()] = $this->createForm(SiteType::class, $site, [
                'action' => $this->generateUrl('app_site_edit', ['id' => $site->getId()]),
                'method' => 'POST',
            ])->createView();
        }
        return $formsEdit;
    }
}
