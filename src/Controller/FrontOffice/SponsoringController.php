<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SponsorRepository;
use App\Repository\ContratSponsorRepository;

#[Route('/sponsoring')]
class SponsoringController extends AbstractController
{
    #[Route('/', name: 'front_sponsoring_index')]
    public function index(Request $request, SponsorRepository $sponsorRepository, ContratSponsorRepository $contratSponsorRepository): Response
    {
        $sponsors = $sponsorRepository->findAll();

        $sponsorNom = $request->query->get('sponsor_nom');
        $dateDebut = $request->query->get('date_debut');

        $dateDebutObj = null;
        if ($dateDebut) {
            try {
                $dateDebutObj = new \DateTime($dateDebut);
            } catch (\Exception $e) {
                $dateDebutObj = null;
            }
        }

        if ($sponsorNom || $dateDebutObj) {
            $contrats = $contratSponsorRepository->searchContrats($sponsorNom, $dateDebutObj);
        } else {
            $qb = $contratSponsorRepository->createQueryBuilder('c')
                ->addSelect('s')
                ->addSelect('e')
                ->innerJoin('c.sponsor', 's')
                ->innerJoin('c.equipe', 'e')
                ->orderBy('c.dateDebut', 'DESC');
            $contrats = $qb->getQuery()->getResult();
        }

        return $this->render('front_office/sponsoring/index.html.twig', [
            'sponsors' => $sponsors,
            'contrats' => $contrats,
            'sponsor_nom' => $sponsorNom,
            'date_debut' => $dateDebut,
        ]);
    }
}
