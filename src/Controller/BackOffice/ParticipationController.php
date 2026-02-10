<?php

namespace App\Controller\BackOffice;

use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/participation')]
class ParticipationController extends AbstractController
{
    #[Route('/', name: 'back_participation_index', methods: ['GET'])]
    public function index(ParticipationRepository $participationRepository): Response
    {
        return $this->render('back_office/participation/index.html.twig', [
            'participations' => $participationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'back_participation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $participation = new Participation();
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            return $this->redirectToRoute('back_participation_index');
        }

        return $this->render('back_office/participation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'back_participation_show', methods: ['GET'])]
    public function show(Participation $participation): Response
    {
        return $this->render('back_office/participation/show.html.twig', [
            'participation' => $participation,
        ]);
    }

    #[Route('/{id}/edit', name: 'back_participation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('back_participation_index');
        }

        return $this->render('back_office/participation/edit.html.twig', [
            'participation' => $participation,
    
        'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'back_participation_delete', methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('back_participation_index');
    }
}
