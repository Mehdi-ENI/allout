<?php

namespace App\Controller;

use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie', name: 'sortie_')]
final class SortieController extends AbstractController
{
    #[Route('/sortie', name: 'app_sortie')]
    public function create(SortieRepository $sortieRepository): Response
    {


        return $this->render('sortie/create.html.twig');
    }
}
