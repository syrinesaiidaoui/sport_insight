<?php

namespace App\Controller\BackOffice;

use App\Entity\Evaluation;
use App\Entity\Entrainement;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/evaluation')]
class EvaluationController extends AbstractController
{
   #[Route('/', name: 'back_evaluation_index', methods: ['GET'])]
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

    /* ================== STATS ================== */
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
    /* =========================================== */

    return $this->render('back_office/evaluation/index.html.twig', [
        'evaluations' => $evaluations,
        'search_nom'  => $searchNom,
        'sort_by'     => $sortBy,
        'sort_dir'    => $sortDir,
        'stats'       => $stats, // 👈 envoyé à Twig
    ]);
}



    #[Route('/new', name: 'back_evaluation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $evaluation = new Evaluation();
        $entrainement = null;
        $entrainementId = $request->query->get('entrainement');
        if ($entrainementId) {
            $entrainement = $em->getRepository(Entrainement::class)->find($entrainementId);
        }

        if ($entrainement) {
            $evaluation->setEntrainement($entrainement);
        }

        $form = $this->createForm(EvaluationType::class, $evaluation, ['entrainement' => $entrainement]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($evaluation);
            $em->flush();
            $this->addFlash('success', 'Evaluation creee avec succes.');
            return $this->redirectToRoute('back_evaluation_index');
        }

        return $this->render('back_office/evaluation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'back_evaluation_show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('back_office/evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    #[Route('/{id}/edit', name: 'back_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evaluation $evaluation, EntityManagerInterface $em): Response
    {
        $entrainement = $evaluation->getEntrainement();
        $form = $this->createForm(EvaluationType::class, $evaluation, ['entrainement' => $entrainement]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Evaluation modifiee avec succes.');
            return $this->redirectToRoute('back_evaluation_index');
        }

        return $this->render('back_office/evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'back_evaluation_delete', methods: ['POST'])]
    public function delete(Request $request, Evaluation $evaluation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->request->get('_token'))) {
            $em->remove($evaluation);
            $em->flush();
            $this->addFlash('success', 'Evaluation supprimee.');
        }
        return $this->redirectToRoute('back_evaluation_index');
    }
}
