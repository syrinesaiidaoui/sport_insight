<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Form\AnnonceType;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/annonce')]
final class AnnonceController extends AbstractController
{
    #[Route(name: 'app_annonce_index', methods: ['GET'])]
    public function index(Request $request, AnnonceRepository $annonceRepository): Response
    {
        $titre = $request->query->get('titre', '');
        $niveau = $request->query->get('niveau', '');
        $sort = $request->query->get('sort', 'recent');

        // Récupérer toutes les annonces
        $allAnnonces = $annonceRepository->findAll();

        // Filtrer par titre
        if ($titre) {
            $allAnnonces = array_filter($allAnnonces, function(Annonce $annonce) use ($titre) {
                return stripos($annonce->getTitre(), $titre) !== false;
            });
        }

        // Filtrer par niveau
        if ($niveau) {
            $allAnnonces = array_filter($allAnnonces, function(Annonce $annonce) use ($niveau) {
                return $annonce->getNiveauRequis() === $niveau;
            });
        }

        // Trier
        if ($sort === 'alpha') {
            usort($allAnnonces, function(Annonce $a, Annonce $b) {
                return strcasecmp($a->getTitre(), $b->getTitre());
            });
        } else {
            // Par défaut: plus récent en premier
            usort($allAnnonces, function(Annonce $a, Annonce $b) {
                return $b->getDatePublication() <=> $a->getDatePublication();
            });
        }

        return $this->render('annonce/index.html.twig', [
            'annonces' => $allAnnonces,
        ]);
    }

    #[Route('/new', name: 'app_annonce_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $annonce = new Annonce();
        // Set current user if logged in, otherwise it's anonymous
        if ($this->getUser()) {
            $annonce->setEntraineur($this->getUser());
        }
        
        $form = $this->createForm(AnnonceType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($annonce);
            $entityManager->flush();

            return $this->redirectToRoute('app_annonce_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('annonce/new.html.twig', [
            'annonce' => $annonce,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_annonce_show', methods: ['GET'])]
    public function show(int $id, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->findByIdWithComments($id);

        if (!$annonce) {
            throw $this->createNotFoundException('Annonce not found');
        }

        return $this->render('annonce/show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_annonce_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AnnonceType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_annonce_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('annonce/edit.html.twig', [
            'annonce' => $annonce,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_annonce_delete', methods: ['POST'])]
    public function delete(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$annonce->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($annonce);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_annonce_index', [], Response::HTTP_SEE_OTHER);
    }
}
