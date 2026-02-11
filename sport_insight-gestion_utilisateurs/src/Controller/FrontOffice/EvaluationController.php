<?php

namespace App\Controller\FrontOffice;

use App\Entity\Evaluation;
use App\Form\Evaluation1Type;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Added the name prefix to match your Twig calls: front_evaluation_...
#[Route('/front/evaluation', name: 'front_evaluation_')]
final class EvaluationController extends AbstractController
{
    // Now results in: front_evaluation_index
    #[Route('', name: 'index', methods: ['GET'])]
public function index(Request $request, EvaluationRepository $evaluationRepository): Response
{
    $searchNom = $request->query->get('search_nom', '');
    $sortBy = $request->query->get('sort_by', '');
    $sortDir = $request->query->get('sort_dir', 'asc');

    $qb = $evaluationRepository->createQueryBuilder('e')
        ->leftJoin('e.joueur', 'j')
        ->addSelect('j')
        ->leftJoin('e.entrainement', 'en')
        ->addSelect('en');

    if ($searchNom) {
        $qb->andWhere('LOWER(j.nom) LIKE :searchNom')
           ->setParameter('searchNom', '%' . strtolower($searchNom) . '%');
    }

    if (in_array($sortBy, ['notePhysique', 'noteTechnique', 'noteTactique'])) {
        $qb->orderBy('e.' . $sortBy, $sortDir === 'desc' ? 'DESC' : 'ASC');
    } else {
        $qb->orderBy('e.id', 'DESC');
    }

    $evaluations = $qb->getQuery()->getResult();

    $total = count($evaluations);
    $sumPhysique = $sumTechnique = $sumTactique = 0;
    foreach ($evaluations as $evaluation) {
        $sumPhysique += $evaluation->getNotePhysique();
        $sumTechnique += $evaluation->getNoteTechnique();
        $sumTactique += $evaluation->getNoteTactique();
    }
    $stats = [
        'physique'  => $total ? round($sumPhysique / $total, 2) : 0,
        'technique' => $total ? round($sumTechnique / $total, 2) : 0,
        'tactique'  => $total ? round($sumTactique / $total, 2) : 0,
    ];

    return $this->render('front_office/evaluation/index.html.twig', [
        'evaluations' => $evaluations,
        'search_nom'  => $searchNom,
        'sort_by'     => $sortBy,
        'sort_dir'    => $sortDir,
        'stats'       => $stats,
    ]);
}


    // Now results in: front_evaluation_new
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evaluation = new Evaluation();
        $form = $this->createForm(Evaluation1Type::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evaluation);
            $entityManager->flush();

            return $this->redirectToRoute('front_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front_office/evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    // Now results in: front_evaluation_show
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('front_office/evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    // Now results in: front_evaluation_edit
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Evaluation1Type::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('front_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front_office/evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    // Now results in: front_evaluation_delete
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($evaluation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('front_evaluation_index', [], Response::HTTP_SEE_OTHER);
    }
    
}