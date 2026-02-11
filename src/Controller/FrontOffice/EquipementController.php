<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\ProductOrder\Order;
use App\Entity\ProductOrder\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
        // Add product to session cart
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $id = $product->getId();

        if (!isset($cart[$id])) {
            $cart[$id] = 0;
        }
        $cart[$id]++;

        $session->set('cart', $cart);

        $this->addFlash('success', 'Produit ajouté au panier.');
        return $this->redirectToRoute('front_equipement_index');
    }

    #[Route('/{id}/remove', name: 'front_equipement_remove')]
    public function remove(Product $product, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $id = $product->getId();

        if (isset($cart[$id])) {
            unset($cart[$id]);
            $session->set('cart', $cart);
            $this->addFlash('success', 'Produit retiré du panier.');
        }

        return $this->redirectToRoute('front_equipement_cart');
    }

    #[Route('/cart', name: 'front_equipement_cart')]
    public function cart(ProductRepository $repo, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0;

        foreach ($cart as $id => $qty) {
            $product = $repo->find($id);
            if ($product) {
                $items[] = ['product' => $product, 'quantity' => $qty];
                $total += floatval($product->getPrice()) * $qty;
            }
        }

        return $this->render('front_office/equipement/cart.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/checkout', name: 'front_equipement_checkout', methods: ['POST'])]
    public function checkout(Request $request, EntityManagerInterface $em, ProductRepository $repo, MailerInterface $mailer, CsrfTokenManagerInterface $csrfManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Please log in to complete your purchase.');
            return $this->redirectToRoute('app_login');
        }

        $token = new CsrfToken('checkout', $request->request->get('_token'));
        if (!$csrfManager->isTokenValid($token)) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('front_equipement_cart');
        }

        $session = $request->getSession();
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('front_equipement_index');
        }

        $orders = [];
        foreach ($cart as $id => $qty) {
            $product = $repo->find($id);
            if (!$product) continue;
            if ($product->getStock() < $qty) {
                $this->addFlash('danger', sprintf('Not enough stock for %s', $product->getName()));
                return $this->redirectToRoute('front_equipement_cart');
            }

            $order = new Order();
            $order->setProduct($product);
            $order->setQuantity($qty);
            $order->setOrderDate(new \DateTime());
            $order->setStatus('confirmed');
            $order->setEntraineur($user);

            $product->setStock($product->getStock() - $qty);

            $em->persist($order);
            $em->persist($product);

            $orders[] = $order;
        }

        $em->flush();

        // Send confirmation email
        $body = $this->renderView('emails/order_confirmation.html.twig', ['user' => $user, 'orders' => $orders]);
        
        $userEmail = $user->getEmail();
        if (!$userEmail) {
            $userEmail = $user->getUserIdentifier();
        }
        
        if ($userEmail) {
            $email = (new Email())
                ->from('no-reply@sport-insight.local')
                ->to($userEmail)
                ->subject('Thank you for your purchase - Sport Insight')
                ->html($body);

            try {
                $mailer->send($email);
            } catch (\Throwable $e) {
                // Log error but continue
            }
        }

        // Clear cart
        $session->remove('cart');

        return $this->redirectToRoute('front_equipement_checkout_success');
    }

    #[Route('/checkout-success', name: 'front_equipement_checkout_success')]
    public function checkoutSuccess(): Response
    {
        return $this->render('front_office/equipement/checkout_success.html.twig');
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
