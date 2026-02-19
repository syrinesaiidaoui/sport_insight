<?php

namespace App\Controller\FrontOffice;

use App\Entity\Entrainement;
use App\Form\EntrainementType;
use App\Repository\EntrainementRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/front/entrainement', name: 'front_entrainement_')]
final class EntrainementController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, EntrainementRepository $entrainementRepository, ParticipationRepository $participationRepository, \App\Repository\EvaluationRepository $evaluationRepository): Response
    {
        $searchType = $request->query->get('search_type', '');
        $sortBy = $request->query->get('sort_by', '');
        $sortDir = $request->query->get('sort_dir', 'asc');

        $qb = $entrainementRepository->createQueryBuilder('e');
        if ($searchType) {
            $qb->andWhere('LOWER(e.type) LIKE :searchType')
                ->setParameter('searchType', '%' . strtolower($searchType) . '%');
        }
        if ($sortBy === 'dateEntrainement') {
            $qb->orderBy('e.dateEntrainement', $sortDir === 'desc' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('e.id', 'DESC');
        }
        $entrainements = $qb->getQuery()->getResult();

        // build participation map for current user
        $participationMap = [];
        $evaluationMap = [];
        $user = $this->getUser();
        if ($user && count($entrainements) > 0) {
            $ids = array_map(fn($e) => $e->getId(), $entrainements);
            $parts = $participationRepository->createQueryBuilder('p')
                ->andWhere('p.joueur = :user')
                ->andWhere('p.entrainement IN (:ids)')
                ->setParameter('user', $user)
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
            foreach ($parts as $p) {
                $participationMap[$p->getEntrainement()->getId()] = $p->getPresence();
            }

            // fetch user's evaluations for these entrainements
            $evals = $evaluationRepository->createQueryBuilder('e')
                ->andWhere('e.joueur = :user')
                ->andWhere('e.entrainement IN (:ids)')
                ->setParameter('user', $user)
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
            foreach ($evals as $ev) {
                $evaluationMap[$ev->getEntrainement()->getId()] = $ev;
            }
        }

        return $this->render('front_office/entrainement/index.html.twig', [
            'entrainements' => $entrainements,
            'search_type' => $searchType,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'participation_map' => $participationMap,
            'evaluation_map' => $evaluationMap,
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, EntrainementRepository $entrainementRepository, ParticipationRepository $participationRepository, \App\Repository\EvaluationRepository $evaluationRepository): JsonResponse
    {
        $q = (string) $request->query->get('q', '');

        $qb = $entrainementRepository->createQueryBuilder('e');
        if ($q !== '') {
            $qb->andWhere('LOWER(e.type) LIKE :q OR LOWER(e.lieu) LIKE :q')
               ->setParameter('q', '%' . strtolower($q) . '%');
        }
        $qb->orderBy('e.id', 'DESC');
        $entrainements = $qb->getQuery()->getResult();

        $participationMap = [];
        $evaluationMap = [];
        $user = $this->getUser();
        if ($user && count($entrainements) > 0) {
            $ids = array_map(fn($e) => $e->getId(), $entrainements);
            $parts = $participationRepository->createQueryBuilder('p')
                ->andWhere('p.joueur = :user')
                ->andWhere('p.entrainement IN (:ids)')
                ->setParameter('user', $user)
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
            foreach ($parts as $p) {
                $participationMap[$p->getEntrainement()->getId()] = $p->getPresence();
            }

            $evals = $evaluationRepository->createQueryBuilder('e')
                ->andWhere('e.joueur = :user')
                ->andWhere('e.entrainement IN (:ids)')
                ->setParameter('user', $user)
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
            foreach ($evals as $ev) {
                $evaluationMap[$ev->getEntrainement()->getId()] = $ev;
            }
        }

        $html = $this->renderView('front_office/entrainement/_cards.html.twig', [
            'entrainements' => $entrainements,
            'participation_map' => $participationMap,
            'evaluation_map' => $evaluationMap,
        ]);

        return new JsonResponse(['html' => $html]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entrainement = new Entrainement();
        $form = $this->createForm(EntrainementType::class, $entrainement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entrainement);
            $entityManager->flush();

            return $this->redirectToRoute('front_entrainement_index');
        }

        return $this->render('front_office/entrainement/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Entrainement $entrainement, ParticipationRepository $participationRepository): Response
    {
        $user = $this->getUser();
        $userParticipation = null;
        if ($user) {
            $userParticipation = $participationRepository->findOneBy(['entrainement' => $entrainement, 'joueur' => $user]);
        }

        return $this->render('front_office/entrainement/show.html.twig', [
            'entrainement' => $entrainement,
            'user_participation' => $userParticipation,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Entrainement $entrainement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EntrainementType::class, $entrainement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('front_entrainement_index');
        }

        return $this->render('front_office/entrainement/edit.html.twig', [
            'form' => $form->createView(),
            'entrainement' => $entrainement,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Entrainement $entrainement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid(
            'delete'.$entrainement->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove($entrainement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('front_entrainement_index');
    }
}
