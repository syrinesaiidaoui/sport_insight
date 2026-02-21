<?php
namespace App\Controller\BackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends AbstractController
{
    #[Route('/admin/invitations', name: 'back_office_invitation_index')]
    public function index(): Response
    {
        // À remplacer par la vraie récupération des invitations pour l'entraîneur connecté
        $invitations = [];
        return $this->render('back_office/invitation/index.html.twig', [
            'invitations' => $invitations,
        ]);
    }
}
