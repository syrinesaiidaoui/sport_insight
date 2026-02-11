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
    public function index(OrderRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $orders = $repo->findAll();
        return $this->render('back_office/order/index.html.twig', ['orders' => $orders]);
    }
}
