<?php

namespace App\Controller\FrontOffice;

use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use App\Repository\ContratSponsorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipe')]
class EquipeController extends AbstractController
{
    #[Route('/{id}', name: 'front_equipe_show', methods: ['GET'])]
    public function show(
        Equipe $equipe,
        ContratSponsorRepository $contratSponsorRepository
    ): Response
    {
        // Récupérer les contrats sponsorship actifs de cette équipe
        $now = new \DateTime();
        $contrats = $contratSponsorRepository->createQueryBuilder('c')
            ->where('c.equipe = :equipe')
            ->andWhere('c.dateDebut <= :now')
            ->andWhere('c.dateFin >= :now')
            ->setParameter('equipe', $equipe)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        return $this->render('front_office/equipe/show.html.twig', [
            'equipe' => $equipe,
            'sponsors_contrats' => $contrats,
        ]);
    }

    #[Route('/{id}/sponsors', name: 'front_equipe_sponsors', methods: ['GET'])]
    public function sponsors(
        Equipe $equipe,
        ContratSponsorRepository $contratSponsorRepository
    ): Response
    {
        // Récupérer les contrats sponsorship de cette équipe (tous les contrats)
        $contrats = $contratSponsorRepository->createQueryBuilder('c')
            ->where('c.equipe = :equipe')
            ->orderBy('c.dateDebut', 'DESC')
            ->setParameter('equipe', $equipe)
            ->getQuery()
            ->getResult();

        return $this->render('front_office/equipe/sponsors.html.twig', [
            'equipe' => $equipe,
            'sponsors_contrats' => $contrats,
        ]);
    }
}
