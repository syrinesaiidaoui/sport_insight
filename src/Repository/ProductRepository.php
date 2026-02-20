<?php

namespace App\Repository;

use App\Entity\ProductOrder\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Return an ordered list of distinct non-null categories.
     * @return string[]
     */
    public function findDistinctCategories(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.category as category')
            ->andWhere('p.category IS NOT NULL')
            ->orderBy('p.category', 'ASC');

        $rows = $qb->getQuery()->getScalarResult();

        return array_map(fn($r) => $r['category'], $rows);
    }

    /**
     * Search products by optional query, category and sort mode.
     * @param string|null $q
     * @param string|null $category
     * @param string|null $sort "name"|"price"|"stock"
     * @return Product[]
     */
    public function searchProducts(?string $q = null, ?string $category = null, ?string $sort = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($q) {
            $qb->andWhere('LOWER(p.name) LIKE :q')
               ->setParameter('q', '%' . strtolower($q) . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :cat')
               ->setParameter('cat', $category);
        }

        switch ($sort) {
            case 'price':
                $qb->orderBy('p.price', 'ASC');
                break;
            case 'stock':
                $qb->orderBy('p.stock', 'DESC');
                break;
            default:
                $qb->orderBy('p.name', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find trending products by summing quantities in orders within the last $days days.
     * Returns an array of ['product' => Product, 'totalSold' => int]
     */
    public function findTrending(int $days = 30, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, SUM(o.quantity) as totalSold')
            ->join('p.orders', 'o')
            ->andWhere('o.orderDate >= :from')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('from', new \DateTime(sprintf('-%d days', $days)))
            ->setParameter('statuses', ['confirmed', 'shipped', 'delivered'])
            ->groupBy('p.id')
            ->orderBy('totalSold', 'DESC')
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        $trending = [];
        foreach ($results as $row) {
            if (is_array($row)) {
                $trending[] = ['product' => $row[0], 'totalSold' => (int)$row['totalSold']];
            }
        }

        return $trending;
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
