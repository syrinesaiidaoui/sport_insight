<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/offre')]
class OffreController extends AbstractController
{
    #[Route('/', name: 'front_offre_index')]
    public function index(): Response
    {
        return $this->render('front_office/offre/index.html.twig');
    }
}
