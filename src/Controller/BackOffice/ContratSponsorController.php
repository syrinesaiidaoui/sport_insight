<?php

namespace App\Controller\BackOffice;

use App\Entity\ContratSponsor;
use App\Form\ContratSponsorType;
use App\Repository\ContratSponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contrat/sponsor')]
final class ContratSponsorController extends AbstractController
{
    #[Route(name: 'app_contrat_sponsor_index', methods: ['GET'])]
    public function index(Request $request, ContratSponsorRepository $contratSponsorRepository): Response
    {
        $sponsorNom = $request->query->get('sponsor_nom');
        $dateDebut = $request->query->get('date_debut');

        // Convertir la date si elle est fournie
        $dateDebutObj = null;
        if ($dateDebut) {
            try {
                $dateDebutObj = new \DateTime($dateDebut);
            } catch (\Exception $e) {
                $dateDebutObj = null;
            }
        }

        // Rechercher avec les critères
        if ($sponsorNom || $dateDebutObj) {
            $contrats = $contratSponsorRepository->searchContrats($sponsorNom, $dateDebutObj);
        } else {
            $contrats = $contratSponsorRepository->findAll();
        }

        return $this->render('back_office/contrat_sponsor/index.html.twig', [
            'contrat_sponsors' => $contrats,
            'sponsor_nom' => $sponsorNom,
            'date_debut' => $dateDebut,
        ]);
    }

    #[Route('/new', name: 'app_contrat_sponsor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contratSponsor = new ContratSponsor();
        $form = $this->createForm(ContratSponsorType::class, $contratSponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contratSponsor);
            $entityManager->flush();

            $referer = $request->headers->get('referer', '');
            $route = str_contains($referer, '/admin') ? 'back_sponsoring_index' : 'front_sponsoring_index';

            return $this->redirectToRoute($route, [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_office/contrat_sponsor/new.html.twig', [
            'contrat_sponsor' => $contratSponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contrat_sponsor_show', methods: ['GET'])]
    public function show(ContratSponsor $contratSponsor): Response
    {
        return $this->render('back_office/contrat_sponsor/show.html.twig', [
            'contrat_sponsor' => $contratSponsor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contrat_sponsor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContratSponsor $contratSponsor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContratSponsorType::class, $contratSponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $referer = $request->headers->get('referer', '');
            $route = str_contains($referer, '/admin') ? 'back_sponsoring_index' : 'front_sponsoring_index';

            return $this->redirectToRoute($route, [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_office/contrat_sponsor/edit.html.twig', [
            'contrat_sponsor' => $contratSponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/pdf', name: 'app_contrat_sponsor_pdf', methods: ['GET'])]
    public function pdf(ContratSponsor $contratSponsor): Response
    {
        return $this->render('back_office/contrat_sponsor/pdf.html.twig', [
            'contrat_sponsor' => $contratSponsor,
        ]);
    }

    #[Route('/{id}', name: 'app_contrat_sponsor_delete', methods: ['POST'])]
    public function delete(Request $request, ContratSponsor $contratSponsor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contratSponsor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contratSponsor);
            $entityManager->flush();
        }

        $referer = $request->headers->get('referer', '');
        $route = str_contains($referer, '/admin') ? 'back_sponsoring_index' : 'front_sponsoring_index';

        return $this->redirectToRoute($route, [], Response::HTTP_SEE_OTHER);
    }
}
