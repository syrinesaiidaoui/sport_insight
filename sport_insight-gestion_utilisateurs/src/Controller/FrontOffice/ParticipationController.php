<?php

namespace App\Controller\FrontOffice;

use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Entrainement;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/front/participation')]
class ParticipationController extends AbstractController
{
    #[Route('/', name: 'front_participation_index', methods: ['GET'])]
    public function index(Request $request, ParticipationRepository $participationRepository): Response
    {
        $presence = $request->query->get('presence');
        if ($presence) {
            $participations = $participationRepository->findByPresence($presence);
        } else {
            $participations = $participationRepository->findAll();
        }
        return $this->render('front_office/participation/index.html.twig', [
            'participations' => $participations,
            'presence' => $presence,
        ]);
    }

    #[Route('/create-for-entrainement/{id}', name: 'front_participation_create_for_entrainement', methods: ['POST'])]
    public function createForEntrainement(int $id, Request $request, ParticipationRepository $participationRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('front_participation_index');
        }

        $em = $entityManager;
        $entrainement = $em->getRepository(Entrainement::class)->find($id);
        if (!$entrainement) {
            return $this->redirectToRoute('front_entrainement_index');
        }

        if (!$this->isCsrfTokenValid('participation'.$entrainement->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('front_entrainement_show', ['id' => $entrainement->getId()]);
        }

        $presence = $request->request->get('presence', 'present');
        $justif = $request->request->get('justificationAbsence', null);

        $existing = $participationRepository->findOneBy(['entrainement' => $entrainement, 'joueur' => $user]);

        if ($existing) {
            $existing->setPresence($presence);
            $existing->setJustificationAbsence($justif);
            $em->flush();
        } else {
            $participation = new \App\Entity\Participation();
            $participation->setEntrainement($entrainement);
            $participation->setJoueur($user);
            $participation->setPresence($presence);
            $participation->setJustificationAbsence($justif);
            $em->persist($participation);
            $em->flush();
        }

        return $this->redirectToRoute('front_entrainement_show', ['id' => $entrainement->getId()]);
    }

    #[Route('/remove-from-entrainement/{id}', name: 'front_participation_remove_for_entrainement', methods: ['POST'])]
    public function removeForEntrainement(int $id, Request $request, ParticipationRepository $participationRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('front_participation_index');
        }

        $em = $entityManager;
        $entrainement = $em->getRepository(Entrainement::class)->find($id);
        if (!$entrainement) {
            return $this->redirectToRoute('front_entrainement_index');
        }

        if (!$this->isCsrfTokenValid('participation'.$entrainement->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('front_entrainement_show', ['id' => $entrainement->getId()]);
        }

        $existing = $participationRepository->findOneBy(['entrainement' => $entrainement, 'joueur' => $user]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        return $this->redirectToRoute('front_entrainement_show', ['id' => $entrainement->getId()]);
    }

    #[Route('/search', name: 'front_participation_search', methods: ['GET'])]
    public function search(Request $request, ParticipationRepository $participationRepository): JsonResponse
    {
        $q = (string) $request->query->get('q', '');

        $qb = $participationRepository->createQueryBuilder('p')
            ->leftJoin('p.joueur', 'j')
            ->addSelect('j')
            ->leftJoin('p.entrainement', 'en')
            ->addSelect('en');

        if ($q !== '') {
            $qb->andWhere('LOWER(j.nom) LIKE :q OR LOWER(j.prenom) LIKE :q OR LOWER(en.type) LIKE :q')
               ->setParameter('q', '%' . strtolower($q) . '%');
        }

        $qb->orderBy('p.id', 'DESC');
        $participations = $qb->getQuery()->getResult();

        $html = $this->renderView('front_office/participation/_cards.html.twig', [
            'participations' => $participations,
        ]);

        return new JsonResponse(['html' => $html]);
    }

    #[Route('/new', name: 'front_participation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $participation = new Participation();
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            return $this->redirectToRoute('front_participation_index');
        }

        return $this->render('front_office/participation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'front_participation_show', methods: ['GET'])]
    public function show(Participation $participation): Response
    {
        return $this->render('front_office/participation/show.html.twig', [
            'participation' => $participation,
        ]);
    }

    #[Route('/{id}/edit', name: 'front_participation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('front_participation_index');
        }

        return $this->render('front_office/participation/edit.html.twig', [
            'participation' => $participation,
    
        'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'front_participation_delete', methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('front_participation_index');
    }
}
