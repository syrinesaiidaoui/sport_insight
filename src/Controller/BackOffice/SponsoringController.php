<?php

namespace App\Controller\BackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/sponsoring')]
class SponsoringController extends AbstractController
{
    #[Route('/', name: 'back_sponsoring_index')]
    public function index(): Response
    {
        return $this->render('back_office/sponsoring/index.html.twig');
    }
}
