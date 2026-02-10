<?php

namespace App\Controller\FrontOffice;

use App\Entity\Participation;
use App\Form\Participation1Type;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// The name prefix 'front_participation_' is applied to every route in this class
#[Route('/front/participation', name: 'front_participation_')]
final class ParticipationController extends AbstractController
{
    // Full route name: front_participation_index
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ParticipationRepository $participationRepository): Response
    {
        return $this->render('front_office/participation/index.html.twig', [
            'participations' => $participationRepository->findAll(),
        ]);
    }

    // Full route name: front_participation_new
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $participation = new Participation();
        $form = $this->createForm(Participation1Type::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            return $this->redirectToRoute('front_participation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front_office/participation/new.html.twig', [
            'participation' => $participation,
            'form' => $form,
        ]);
    }

    // Full route name: front_participation_show
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Participation $participation): Response
    {
        // Note: Make sure this template path is correct (usually matches the folder structure)
        return $this->render('front_office/participation/show.html.twig', [
            'participation' => $participation,
        ]);
    }

    // Full route name: front_participation_edit
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Participation1Type::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('front_participation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front_office/participation/edit.html.twig', [
            'participation' => $participation,
            'form' => $form,
        ]);
    }

    // Full route name: front_participation_delete
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('front_participation_index', [], Response::HTTP_SEE_OTHER);
    }
}