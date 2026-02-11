<?php

namespace App\Repository;

use App\Entity\ContratSponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratSponsor>
 */
class ContratSponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratSponsor::class);
    }

    /**
     * Recherche les contrats par nom de sponsor et/ou date début
     */
    public function searchContrats(?string $sponsorNom = null, ?\DateTime $dateDebut = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.sponsor', 's')
            ->orderBy('c.dateDebut', 'DESC');

        if ($sponsorNom) {
            $qb->andWhere('s.nom LIKE :sponsorNom')
                ->setParameter('sponsorNom', '%' . $sponsorNom . '%');
        }

        if ($dateDebut) {
            $qb->andWhere('c.dateDebut >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        return $qb->getQuery()->getResult();
    }

    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ContratSponsor
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
