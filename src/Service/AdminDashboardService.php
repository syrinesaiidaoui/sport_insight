<?php

namespace App\Service;

use App\Entity\ProductOrder\Order;
use App\Repository\OrderRepository;

class AdminDashboardService
{
    private const REVENUE_STATUSES = ['confirmed', 'shipped', 'delivered'];

    public function __construct(private readonly OrderRepository $orderRepository)
    {
    }

    public function buildDashboardData(): array
    {
        $orders = $this->orderRepository
            ->createQueryBuilder('o')
            ->leftJoin('o.product', 'p')
            ->addSelect('p')
            ->orderBy('o.orderDate', 'ASC')
            ->getQuery()
            ->getResult();

        $statusCounts = [
            'pending' => 0,
            'confirmed' => 0,
            'shipped' => 0,
            'delivered' => 0,
            'rejected' => 0,
        ];

        $overview = [
            'totalSales' => 0,
            'totalRevenue' => 0.0,
            'totalOrders' => count($orders),
            'confirmedOrders' => 0,
            'pendingOrders' => 0,
        ];

        $daily = $this->initDailyBuckets(14);
        $monthly = $this->initMonthlyBuckets(6);

        foreach ($orders as $order) {
            if (!$order instanceof Order) {
                continue;
            }

            $status = (string) $order->getStatus();
            $quantity = $this->resolveSalesQuantity($order);
            $lineTotal = $order->getComputedTotal();
            $orderDate = $order->getOrderDate();

            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            if ($status === 'confirmed') {
                $overview['confirmedOrders']++;
            }
            if ($status === 'pending') {
                $overview['pendingOrders']++;
            }

            $isRevenueStatus = in_array($status, self::REVENUE_STATUSES, true);
            if ($isRevenueStatus) {
                $overview['totalSales'] += $quantity;
                $overview['totalRevenue'] += $lineTotal;
            }

            if ($orderDate instanceof \DateTimeInterface) {
                $dayKey = $orderDate->format('Y-m-d');
                if (isset($daily[$dayKey])) {
                    $daily[$dayKey]['orders']++;
                    if ($isRevenueStatus) {
                        $daily[$dayKey]['sales'] += $quantity;
                        $daily[$dayKey]['revenue'] += $lineTotal;
                    }
                }

                $monthKey = $orderDate->format('Y-m');
                if (isset($monthly[$monthKey])) {
                    $monthly[$monthKey]['orders']++;
                    if ($isRevenueStatus) {
                        $monthly[$monthKey]['sales'] += $quantity;
                        $monthly[$monthKey]['revenue'] += $lineTotal;
                    }
                }
            }
        }

        $overview['totalRevenue'] = round($overview['totalRevenue'], 2);

        return [
            'overview' => $overview,
            'statusBreakdown' => $statusCounts,
            'dailyStats' => array_values($daily),
            'monthlyStats' => array_values($monthly),
        ];
    }

    private function initDailyBuckets(int $days): array
    {
        $buckets = [];
        $today = new \DateTimeImmutable('today');

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->modify(sprintf('-%d days', $i));
            $key = $date->format('Y-m-d');
            $buckets[$key] = [
                'key' => $key,
                'label' => $date->format('d M'),
                'orders' => 0,
                'sales' => 0,
                'revenue' => 0.0,
            ];
        }

        return $buckets;
    }

    private function initMonthlyBuckets(int $months): array
    {
        $buckets = [];
        $currentMonth = new \DateTimeImmutable('first day of this month');

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $currentMonth->modify(sprintf('-%d months', $i));
            $key = $date->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $date->format('M Y'),
                'orders' => 0,
                'sales' => 0,
                'revenue' => 0.0,
            ];
        }

        return $buckets;
    }

    private function resolveSalesQuantity(Order $order): int
    {
        if (!$order->getItems()->isEmpty()) {
            $sum = 0;
            foreach ($order->getItems() as $item) {
                $sum += max(0, (int) $item->getQuantity());
            }
            return $sum;
        }

        return max(0, (int) $order->getQuantity());
    }
}
