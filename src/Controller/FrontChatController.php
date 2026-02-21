<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Entity\ChatMessage;
use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FrontChatController extends AbstractController
{
    #[Route('/annonce/{id}/chat', name: 'front_annonce_chat', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function chat(Annonce $annonce, ChatMessageRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $entraineur = $annonce->getEntraineur();

        // Mark messages as read if the current user is the recipient
        $unreadMessages = $repo->findBy(['annonce' => $annonce, 'destinataire' => $user, 'isRead' => false]);
        foreach ($unreadMessages as $unreadMessage) {
            $unreadMessage->setIsRead(true);
            $em->persist($unreadMessage);
        }
        $em->flush();

        $messages = $repo->findByAnnonceAndUsers($annonce, $user, $entraineur);
        return $this->render('front_office/chat.html.twig', [
            'annonce' => $annonce,
            'entraineur' => $entraineur,
            'messages' => $messages,
        ]);
    }

    #[Route('/annonce/{id}/chat/send', name: 'front_annonce_chat_send', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function send(Annonce $annonce, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $entraineur = $annonce->getEntraineur();
        $data = json_decode($request->getContent(), true);
        $msg = new ChatMessage();
        $msg->setAnnonce($annonce);
        $msg->setAuteur($user);
        $msg->setDestinataire($entraineur);
        $msg->setMessage($data['message'] ?? '');
        $msg->setCreatedAt(new \DateTime());
        $msg->setIsRead(false);
        $msg->setNotificationSent(false);
        $em->persist($msg);
        $em->flush();
        return new JsonResponse([
            'success' => true,
            'message' => $msg->getMessage(),
            'auteur' => $user->getNom(),
            'createdAt' => $msg->getCreatedAt()->format('Y-m-d H:i'),
        ]);
    }
}
