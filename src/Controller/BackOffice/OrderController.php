<?php

namespace App\Controller\BackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/orders')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'back_orders_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_order_index');
    }
}
