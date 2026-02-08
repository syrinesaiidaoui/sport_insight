<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/entrainement')]
class EntrainementController extends AbstractController
{
    #[Route('/', name: 'front_entrainement_index')]
    public function index(): Response
    {
        return $this->render('front_office/entrainement/index.html.twig');
    }
}
