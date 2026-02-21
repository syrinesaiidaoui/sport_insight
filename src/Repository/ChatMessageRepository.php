<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * @return ChatMessage[]
     */
    public function findByAnnonceAndUsers($annonce, $user1, $user2): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.annonce = :annonce')
            ->andWhere('(m.auteur = :user1 AND m.destinataire = :user2) OR (m.auteur = :user2 AND m.destinataire = :user1)')
            ->setParameter('annonce', $annonce)
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    /**
     * @return ChatMessage[]
     */
    public function findPendingNotifications(): array
    {
        $oneMinuteAgo = new \DateTime('-1 minute');

        return $this->createQueryBuilder('m')
            ->andWhere('m.isRead = :isRead')
            ->andWhere('m.notificationSent = :notificationSent')
            ->andWhere('m.createdAt <= :oneMinuteAgo')
            ->setParameter('isRead', false)
            ->setParameter('notificationSent', false)
            ->setParameter('oneMinuteAgo', $oneMinuteAgo)
            ->getQuery()
            ->getResult();
    }
}
