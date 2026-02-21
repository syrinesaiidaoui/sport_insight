<?php

namespace App\Controller\BackOffice;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/orders')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'back_orders_index')]
    public function index(OrderRepository $repo, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        // Pagination for admin orders
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = 5;
        $qb = $repo->createQueryBuilder('o')->orderBy('o.orderDate', 'DESC');
        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $orders = $qb->getQuery()->getResult();
        $totalOrders = (int)$repo->createQueryBuilder('o')->select('COUNT(o.id)')->getQuery()->getSingleScalarResult();
        $totalPages = (int)ceil($totalOrders / $perPage);
        return $this->render('back_office/order/index.html.twig', [
            'orders' => $orders,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }
}
