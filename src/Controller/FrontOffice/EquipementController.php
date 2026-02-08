<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Order;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/equipement')]
class EquipementController extends AbstractController
{
    #[Route('/', name: 'front_equipement_index')]
    public function index(ProductRepository $repo): Response
    {
        $products = $repo->findAll();
        return $this->render('front_office/equipement/index.html.twig', ['products' => $products]);
    }

    #[Route('/{id}/buy', name: 'front_equipement_buy')]
    public function buy(Product $product, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter pour acheter.');
            return $this->redirectToRoute('app_login');
        }

        if ($product->getStock() <= 0) {
            $this->addFlash('danger', 'Produit en rupture de stock.');
            return $this->redirectToRoute('front_equipement_index');
        }

        $order = new Order();
        $order->setProduct($product);
        $order->setQuantity(1);
        $order->setOrderDate(new \DateTime());
        $order->setStatus('pending');
        $order->setEntraineur($user);

        $product->setStock($product->getStock() - 1);

        $em->persist($order);
        $em->persist($product);
        $em->flush();

        $this->addFlash('success', 'Achat effectué.');
        return $this->redirectToRoute('front_equipement_index');
    }

    #[Route('/orders', name: 'front_equipement_orders')]
    public function orders(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front_office/equipement/orders.html.twig', [
            'orders' => $user->getOrders(),
        ]);
    }
}
