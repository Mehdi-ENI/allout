<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des villes.
 *
 * Gère l'affichage, l'ajout, la modification et la suppression des villes
 * directement depuis une interface inline sans pages séparées.
 */
#[Route('/ville')]
final class VilleController extends AbstractController
{
    /**
     * Affiche la liste des villes avec les formulaires d'ajout et d'édition inline.
     *
     * Gère également la soumission du formulaire d'ajout via POST.
     * La liste peut être filtrée par nom via le paramètre GET "recherche".
     *
     * @param Request                $request         La requête HTTP courante
     * @param VilleRepository        $villeRepository Le dépôt des villes
     * @param EntityManagerInterface $entityManager   Le gestionnaire d'entités Doctrine
     *
     * @return Response La vue de la liste des villes
     */
    #[Route(name: 'app_ville_index', methods: ['GET', 'POST'])]
    public function index(Request $request, VilleRepository $villeRepository, EntityManagerInterface $entityManager): Response
    {
        $recherche = $request->query->get('recherche');
        $villes = $recherche ? $villeRepository->findByNomContient($recherche) : $villeRepository->findAll();

        $nouvelleVille = new Ville();
        $formNew = $this->createForm(VilleType::class, $nouvelleVille, [
            'action' => $this->generateUrl('app_ville_index'),
            'method' => 'POST',
        ]);
        $formNew->handleRequest($request);

        if ($formNew->isSubmitted() && $formNew->isValid()) {
            $entityManager->persist($nouvelleVille);
            $entityManager->flush();
            $this->addFlash('success', 'Ville ajoutée avec succès.');
            return $this->redirectToRoute('app_ville_index', [], Response::HTTP_SEE_OTHER);
        } elseif ($formNew->isSubmitted()) {
            foreach ($formNew->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('ville/index.html.twig', [
            'villes' => $villes,
            'recherche' => $recherche,
            'formNew' => $formNew->createView(),
            'formsEdit' => $this->buildFormsEdit($villes),
        ]);
    }

    /**
     * Traite la modification d'une ville existante.
     *
     * Reçoit uniquement les requêtes POST depuis le formulaire d'édition inline.
     * Redirige toujours vers la liste, avec un flash de succès ou d'erreur selon le résultat.
     *
     * @param Request                $request       La requête HTTP courante
     * @param Ville                  $ville         La ville à modifier (résolue automatiquement par l'id)
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     *
     * @return Response Redirection vers la liste des villes
     */
    #[Route('/{id}/edit', name: 'app_ville_edit', methods: ['POST'])]
    public function edit(Request $request, Ville $ville, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ville modifiée avec succès.');
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_ville_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Supprime une ville après vérification du token CSRF et des contraintes métier.
     *
     * La suppression est refusée si la ville est rattachée à des lieux.
     * En cas d'erreur inattendue en base de données, une exception est interceptée
     * et un message d'erreur générique est affiché à l'utilisateur.
     *
     * @param Request                $request       La requête HTTP courante
     * @param Ville                  $ville         La ville à supprimer (résolue automatiquement par l'id)
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités Doctrine
     *
     * @return Response Redirection vers la liste des villes
     */
    #[Route('/{id}', name: 'app_ville_delete', methods: ['POST'])]
    public function delete(Request $request, Ville $ville, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $ville->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_ville_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($ville->getLieus()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette ville : des lieux y sont rattachés.');
            return $this->redirectToRoute('app_ville_index', [], Response::HTTP_SEE_OTHER);
        }

        try {
            $entityManager->remove($ville);
            $entityManager->flush();
            $this->addFlash('success', 'Ville supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
        }

        return $this->redirectToRoute('app_ville_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Construit les formulaires d'édition pour chaque ville de la liste.
     *
     * Chaque formulaire est configuré avec l'action pointant vers la route d'édition
     * de la ville correspondante. Les vues sont générées directement pour être passées au template.
     *
     * @param Ville[] $villes La liste des villes pour lesquelles générer un formulaire
     *
     * @return array<int, FormView> Un tableau indexé par l'id de la ville contenant les vues de formulaire
     */
    private function buildFormsEdit(array $villes): array
    {
        $formsEdit = [];
        foreach ($villes as $ville) {
            $formsEdit[$ville->getId()] = $this->createForm(VilleType::class, $ville, [
                'action' => $this->generateUrl('app_ville_edit', ['id' => $ville->getId()]),
                'method' => 'POST',
            ])->createView();
        }
        return $formsEdit;
    }
}
