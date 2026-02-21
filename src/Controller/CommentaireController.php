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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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

        // If user is logged in, check for face verification
        if ($this->getUser()) {
            $faceVerified = $request->request->get('face_verified');
            if ($faceVerified !== '1') {
                $this->addFlash('moderation_error', 'Veuillez vérifier votre identité par reconnaissance faciale avant de publier.');
                return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
            }
        }

        // AI Moderation Check
        $moderationResult = $moderationService->checkComment($contenu);

        // If blocked, we could either show an error or just redirect
        if ($moderationResult['status'] === 'BLOCKED') {
            $this->addFlash('moderation_error', 'Ce type de message doit être bloqué');
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

        // Gestion du dépôt de CV par le joueur
        if ($this->getUser() && $request->files->get('cv_file')) {
            $user = $this->getUser();
            /** @var \App\Entity\User $user */
            $cvFile = $request->files->get('cv_file');
            $user->setCvFile($cvFile);
            $entityManager->persist($user);
        }

        $entityManager->flush();


        $this->addFlash('success', 'Votre commentaire a été publié avec succès.');

        return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_commentaire_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
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
                    $this->addFlash('moderation_error', 'Ce type de message doit être bloqué');
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

    #[Route('/verify-face', name: 'app_commentaire_verify_face', methods: ['POST'])]
    public function verifyFace(Request $request, \App\Service\ModerationService $moderationService, ParameterBagInterface $params): \Symfony\Component\HttpFoundation\JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new \Symfony\Component\HttpFoundation\JsonResponse(['verified' => false, 'error' => 'User not logged in'], 403);
            }

            /** @var \App\Entity\User $user */
            if (!$user->getPhoto()) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'verified' => false,
                    'error' => 'Votre profil n\'a pas de photo ! Veuillez en ajouter une dans votre profil.'
                ], 400);
            }

            $data = json_decode($request->getContent(), true);
            $capturedBase64 = $data['image'] ?? null;

            if (!$capturedBase64) {
                return new \Symfony\Component\HttpFoundation\JsonResponse(['verified' => false, 'error' => 'No captured image provided'], 400);
            }

            $projectDir = $params->get('kernel.project_dir');
            $referencePath = $projectDir . '/public/uploads/photos/' . $user->getPhoto();

            if (!file_exists($referencePath)) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'verified' => false,
                    'error' => 'Photo de profil absente sur le serveur. Veuillez re-telecharger votre photo dans votre profil pour que la reconnaissance faciale puisse fonctionner.'
                ], 400);
            }

            $result = $moderationService->verifyFace($capturedBase64, $referencePath);

            return new \Symfony\Component\HttpFoundation\JsonResponse($result);
        } catch (\Exception $e) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'verified' => false,
                'error' => 'Erreur interne Symfony : ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'app_commentaire_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
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

    #[Route('/{id}/like', name: 'app_commentaire_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $commentaire->setNbLikes($commentaire->getNbLikes() + 1);
        $entityManager->flush();

        return $this->redirectToRoute('app_annonce_show', ['id' => $commentaire->getAnnonce()->getId()]);
    }
}
