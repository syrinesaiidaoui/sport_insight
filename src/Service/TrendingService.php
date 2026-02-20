<?php

namespace App\Service;

use App\Repository\ProductRepository;

class TrendingService
{
    public function __construct(private ProductRepository $productRepository) {}

    /**
     * Get trending products for the given period (days) and limit.
     * Returns an array of ['product' => Product, 'totalSold' => int]
     */
    public function getTrending(int $days = 30, int $limit = 5): array
    {
        return $this->productRepository->findTrending($days, $limit);
    }
}
