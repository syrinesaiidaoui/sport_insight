<?php

namespace App\Controller;

use App\Entity\Matchs;
use App\Form\MatchsType;
use App\Repository\MatchsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/front/matchs')]
final class FrontMatchsController extends AbstractController
{
    #[Route(name: 'app_front_matchs_index', methods: ['GET'])]
    public function index(MatchsRepository $matchsRepository, Request $request): Response
    {
        $sortOrder = $request->query->get('order', 'asc');

        $matchs = $matchsRepository->createQueryBuilder('m')
            ->orderBy('m.id', $sortOrder)
            ->getQuery()
            ->getResult();

        $deleteForms = [];
        foreach ($matchs as $match) {
            $deleteForms[$match->getId()] = $this->createFormBuilder()
                ->setAction($this->generateUrl('app_front_matchs_delete', ['id' => $match->getId()]))
                ->setMethod('POST')
                ->getForm()
                ->createView();
        }

        return $this->render('front_office/matchs/index.html.twig', [
            'matchs' => $matchs,
            'currentOrder' => $sortOrder,
            'delete_forms' => $deleteForms,
        ]);
    }

    #[Route('/new', name: 'app_front_matchs_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $match = new Matchs();
        $form = $this->createForm(MatchsType::class, $match);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($match);
            $entityManager->flush();

            return $this->redirectToRoute('app_front_matchs_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front_office/matchs/new.html.twig', [
            'match' => $match,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_front_matchs_show', methods: ['GET'])]
    public function show(Matchs $match): Response
    {
        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_front_matchs_delete', ['id' => $match->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('front_office/matchs/show.html.twig', [
            'match' => $match,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_front_matchs_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Matchs $match, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MatchsType::class, $match);
        $form->handleRequest($request);

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_front_matchs_delete', ['id' => $match->getId()]))
            ->setMethod('POST')
            ->getForm();

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_front_matchs_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front_office/matchs/edit.html.twig', [
            'match' => $match,
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_front_matchs_delete', methods: ['POST'])]
    public function delete(Request $request, Matchs $match, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_' . $match->getId(), $request->request->get('_token'))) {
            $entityManager->remove($match);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_front_matchs_index', [], Response::HTTP_SEE_OTHER);
    }
}