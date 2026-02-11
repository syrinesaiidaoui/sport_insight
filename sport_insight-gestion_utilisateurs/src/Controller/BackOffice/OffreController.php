<?php

namespace App\Controller\BackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/offre')]
class OffreController extends AbstractController
{
    #[Route('/', name: 'back_offre_index')]
    public function index(): Response
    {
        return $this->render('back_office/offre/index.html.twig');
    }
}
