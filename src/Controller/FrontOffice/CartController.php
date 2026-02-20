<?php

namespace App\Controller\FrontOffice;

use App\Service\CartService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// security attribute import removed for public/no-login mode

#[Route('/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        $cart = $cartService->getCart();
        $total = $cartService->getCartTotal();

        return $this->render('front_office/cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
            'cartCount' => $cartService->getCartCount(),
        ]);
    }

    #[Route('/update/{productId}', name: 'update', methods: ['POST'])]
    public function update(
        int $productId,
        Request $request,
        CartService $cartService
    ): Response {
        $quantity = max(0, (int)$request->request->get('quantity', 0));

        try {
            if ($quantity === 0) {
                $cartService->removeFromCart($productId);
                $this->addFlash('success', 'Produit supprimé du panier');
            } else {
                $cartService->updateQuantity($productId, $quantity);
                $this->addFlash('success', 'Quantité mise à jour');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la mise à jour');
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{productId}', name: 'remove', methods: ['POST'])]
    public function remove(int $productId, CartService $cartService): Response
    {
        try {
            $cartService->removeFromCart($productId);
            $this->addFlash('success', 'Produit supprimé du panier');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression');
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(CartService $cartService): Response
    {
        $cartService->clearCart();
        $this->addFlash('success', 'Panier vidé');

        return $this->redirectToRoute('app_shop_index');
    }
}
