<?php

namespace App\Controller\BackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/match')]
class MatchController extends AbstractController
{
    #[Route('/', name: 'back_match_index')]
    public function index(): Response
    {
        return $this->render('back_office/match/index.html.twig');
    }
}
