<?php

namespace App\Controller\BackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/equipement')]
class EquipementController extends AbstractController
{
    #[Route('/', name: 'back_equipement_index')]
    public function index(): Response
    {
        return $this->render('back_office/equipement/index.html.twig');
    }
}
