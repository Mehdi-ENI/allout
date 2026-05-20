<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Service\SortieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie', name: 'sortie_')]
final class SortieController extends AbstractController
{
    #[Route('/create', name: 'create')]
    public function create(Request $request, SortieService $sortieService): Response
    {

        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            // Organisateur connecté
            $sortie->setOrganisateur($this->getUser());

            try {

                $sortieService->creerSortie($sortie);

                $this->addFlash('success', 'Sortie créée avec succès');

                return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);

            } catch (\Exception $e) {

//                dd($e->getMessage());
                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->render('sortie/create.html.twig', [
            'sortieForm' => $sortieForm->createView(),
        ]);
    }

    #[Route('/list', name: 'list')]
    public function list(SortieRepository $sortieRepository,
                         SiteRepository   $siteRepository,
                         Request          $request): Response
    {
        dump($request->query->all());
        $sortie = $sortieRepository->findAll();
        $sortie = $sortieRepository->findWithFilters($request->query->all());
        $sites = $siteRepository->findAll();

        return $this->render('sortie/list.html.twig', [
            'sorties' => $sortie,
            'sites' => $sites
        ]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(Request $request, SortieService $sortieService, int $id): Response
    {
        try {
            $sortie = $sortieService->getSortieDetail($id);

            return $this->render('sortie/detail.html.twig', [
                'sortie' => $sortie
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('sortie_list');
        }
    }

}
