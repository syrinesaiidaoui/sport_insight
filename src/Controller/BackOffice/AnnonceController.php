<?php

namespace App\Controller\BackOffice;

use App\Entity\Annonce;
use App\Form\AnnonceType;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back/annonce')]
final class AnnonceController extends AbstractController
{
    #[Route('/', name: 'back_annonce_index', methods: ['GET'])]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        $annonces = $annonceRepository->findBy([], ['datePublication' => 'DESC']);

        return $this->render('back_office/annonce/index.html.twig', [
            'annonces' => $annonces,
        ]);
    }

    #[Route('/{id}', name: 'back_annonce_show', methods: ['GET'])]
    public function show(int $id, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->findByIdWithComments($id);

        if (!$annonce) {
            throw $this->createNotFoundException('Annonce not found');
        }

        return $this->render('back_office/annonce/show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/new', name: 'back_annonce_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $annonce = new Annonce();
        $form = $this->createForm(AnnonceType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($annonce);
            $entityManager->flush();
            $this->addFlash('success', 'Annonce créée.');

            return $this->redirectToRoute('back_annonce_index');
        }

        return $this->render('back_office/annonce/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'back_annonce_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AnnonceType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Annonce mise à jour.');

            return $this->redirectToRoute('back_annonce_index');
        }

        return $this->render('back_office/annonce/edit.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'back_annonce_delete', methods: ['POST'])]
    public function delete(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$annonce->getId(), $request->request->get('_token'))) {
            $entityManager->remove($annonce);
            $entityManager->flush();
            $this->addFlash('success', 'Annonce supprimée.');
        }

        return $this->redirectToRoute('back_annonce_index');
    }
}
