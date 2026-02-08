<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sponsoring')]
class SponsoringController extends AbstractController
{
    #[Route('/', name: 'front_sponsoring_index')]
    public function index(): Response
    {
        return $this->render('front_office/sponsoring/index.html.twig');
    }
}
