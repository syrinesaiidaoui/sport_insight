<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/equipement')]
class EquipementController extends AbstractController
{
    #[Route('/', name: 'front_equipement_index')]
    public function index(): Response
    {
        return $this->render('front_office/equipement/index.html.twig');
    }
}
