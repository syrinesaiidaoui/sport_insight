<?php

namespace App\Controller\BackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/entrainement')]
class EntrainementController extends AbstractController
{
    #[Route('/', name: 'back_entrainement_index')]
    public function index(): Response
    {
        return $this->render('back_office/entrainement/index.html.twig');
    }
}
