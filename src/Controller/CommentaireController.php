<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Annonce;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commentaire')]
final class CommentaireController extends AbstractController
{
    #[Route('/new/{annonce_id}', name: 'app_commentaire_new', methods: ['POST'])]
    public function new(Request $request, int $annonce_id, EntityManagerInterface $entityManager, AnnonceRepository $annonceRepository, \App\Service\ModerationService $moderationService): Response
    {
        $annonce = $annonceRepository->find($annonce_id);
        if (!$annonce) {
            throw $this->createNotFoundException('Annonce not found');
        }

        $contenu = $request->request->get('contenu');
        $auteurAnonyme = $request->request->get('auteur_anonyme', '');

        if (empty($contenu)) {
            return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
        }

        // AI Moderation Check
        $moderationResult = $moderationService->checkComment($contenu);

        // If blocked, we could either show an error or just redirect
        if ($moderationResult['status'] === 'BLOCKED') {
            $this->addFlash('danger', 'Votre commentaire a été bloqué par le système de modération automatique : ' . $moderationResult['reason']);
            return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
        }

        $commentaire = new Commentaire();
        $commentaire->setAnnonce($annonce);
        $commentaire->setDateCommentaire(new \DateTime());
        $commentaire->setContenu($moderationResult['cleanedText'] ?? $contenu);
        $commentaire->setModerationStatus($moderationResult['status']);
        $commentaire->setModerationReason($moderationResult['reason'] ?? null);

        // Si utilisateur connecté, set le joueur, sinon set l'auteur anonyme
        if ($this->getUser()) {
            $commentaire->setJoueur($this->getUser());
        } else {
            if (empty($auteurAnonyme)) {
                $auteurAnonyme = 'Anonyme';
            }
            $commentaire->setAuteurAnonyme($auteurAnonyme);
        }

        $entityManager->persist($commentaire);
        $entityManager->flush();

        $this->addFlash('success', 'Votre commentaire a été publié avec succès.');

        return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_commentaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager, \App\Service\ModerationService $moderationService): Response
    {
        // Vérifier que l'utilisateur est le propriétaire du commentaire
        if ($commentaire->getJoueur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres commentaires.');
        }

        $annonce = $commentaire->getAnnonce();

        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('contenu');

            if (!empty($contenu)) {
                // AI Moderation Check
                $moderationResult = $moderationService->checkComment($contenu);

                if ($moderationResult['status'] === 'BLOCKED') {
                    $this->addFlash('danger', 'Modification bloquée par l\'IA : ' . $moderationResult['reason']);
                    return $this->render('commentaire/edit.html.twig', [
                        'commentaire' => $commentaire,
                        'annonce' => $annonce,
                    ]);
                }

                $commentaire->setContenu($moderationResult['cleanedText'] ?? $contenu);
                $commentaire->setModerationStatus($moderationResult['status']);
                $commentaire->setModerationReason($moderationResult['reason'] ?? null);

                $entityManager->flush();
                return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
            }
        }

        return $this->render('commentaire/edit.html.twig', [
            'commentaire' => $commentaire,
            'annonce' => $annonce,
        ]);
    }

    #[Route('/{id}', name: 'app_commentaire_delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est le propriétaire du commentaire
        if ($commentaire->getJoueur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres commentaires.');
        }

        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $request->getPayload()->getString('_token'))) {
            $annonce = $commentaire->getAnnonce();
            $entityManager->remove($commentaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
        }

        return $this->redirectToRoute('app_annonce_show', ['id' => $commentaire->getAnnonce()->getId()]);
    }

    #[Route('/{id}/like', name: 'app_commentaire_like', methods: ['POST'])]
    public function like(Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $commentaire->setNbLikes($commentaire->getNbLikes() + 1);
        $entityManager->flush();

        return $this->redirectToRoute('app_annonce_show', ['id' => $commentaire->getAnnonce()->getId()]);
    }

    #[Route('/check', name: 'app_commentaire_check', methods: ['POST'])]
    public function check(Request $request, \App\Service\ModerationService $moderationService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';

        if (empty($text)) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['status' => 'PENDING', 'reason' => '']);
        }

        $result = $moderationService->checkComment($text);
        return new \Symfony\Component\HttpFoundation\JsonResponse($result);
    }
}
