<?php
namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Retourne tous les messages entre deux utilisateurs (ordre chronologique)
     */
    public function findChatMessages(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :u1 AND m.receiver = :u2) OR (m.sender = :u2 AND m.receiver = :u1)')
            ->setParameter('u1', $user1)
            ->setParameter('u2', $user2)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
