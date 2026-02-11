<?php

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    /**
     * @return Participation[] Returns an array of Participation objects filtered by presence
     */
    public function findByPresence(string $presence): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.presence = :presence')
            ->setParameter('presence', $presence)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    public function findOneBySomeField($value): ?Participation
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
