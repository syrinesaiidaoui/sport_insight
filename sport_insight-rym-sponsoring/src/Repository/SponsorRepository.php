<?php

namespace App\Repository;

use App\Entity\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    /**
     * Recherche les sponsors par email et/ou budget
     */
    public function searchSponsors(?string $email = null, ?float $budget = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.nom', 'ASC');

        if ($email) {
            $qb->andWhere('s.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }

        if ($budget !== null && $budget > 0) {
            $qb->andWhere('s.budget = :budget')
                ->setParameter('budget', $budget);
        }

        return $qb->getQuery()->getResult();
    }

    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sponsor
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
