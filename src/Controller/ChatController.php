<?php
namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ChatController extends AbstractController
{
    #[Route('/chat/{id}', name: 'chat_with_user')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function chat(User $user): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('chat/chat.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/chat/send', name: 'chat_send', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function send(Request $request, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $receiverId = $data['receiver_id'] ?? null;
        $content = $data['content'] ?? '';
        $receiver = $userRepo->find($receiverId);
        if (!$receiver || !$content) {
            return $this->json(['error' => 'Invalid data'], 400);
        }
        $message = new Message();
        $message->setSender($this->getUser());
        $message->setReceiver($receiver);
        $message->setContent($content);
        $message->setSentAt(new \DateTime());
        $em->persist($message);
        $em->flush();
        return $this->json(['success' => true]);
    }
}
