<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/match')]
class MatchController extends AbstractController
{
    #[Route('/', name: 'front_match_index')]
    public function index(): Response
    {
        return $this->render('front_office/match/index.html.twig');
    }
}
