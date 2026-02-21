<?php

namespace App\Controller\FrontOffice;

use App\Entity\ProductOrder\Order;
use App\Service\CartService;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// security attribute import removed for public/no-login mode

#[Route('/checkout', name: 'app_checkout_')]
class CheckoutController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CartService $cartService,
        ValidationService $validationService,
        EntityManagerInterface $entityManager
    ): Response {
        $cart = $cartService->getCart();

        // Redirect if cart is empty
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide');
            return $this->redirectToRoute('app_shop_index');
        }

        if ($request->isMethod('POST')) {
            // Get form data
            $email = trim($request->request->get('email', ''));
            $name = trim($request->request->get('name', ''));
            $address = trim($request->request->get('address', ''));
            $city = trim($request->request->get('city', ''));
            $zipCode = trim($request->request->get('zipCode', ''));
            $paymentMethod = (string) $request->request->get('paymentMethod', 'cod');
            $cardHolder = trim((string) $request->request->get('card_holder', ''));
            $cardNumber = preg_replace('/\s+/', '', (string) $request->request->get('card_number', ''));
            $cardExpiry = trim((string) $request->request->get('card_expiry', ''));
            $cardCvv = trim((string) $request->request->get('card_cvv', ''));

            // Validate inputs
            $errors = [];

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email invalide';
            }

            if (empty($name) || strlen($name) < 2) {
                $errors['name'] = 'Nom requis (minimum 2 caractères)';
            }

            if (empty($address)) {
                $errors['address'] = 'Adresse requise';
            }

            if (empty($city)) {
                $errors['city'] = 'Ville requise';
            }

            if (empty($zipCode) || !preg_match('/^\d{5}$/', $zipCode)) {
                $errors['zipCode'] = 'Code postal invalide (5 chiffres)';
            }
            if (!in_array($paymentMethod, ['cod', 'online'], true)) {
                $errors['paymentMethod'] = 'Methode de paiement invalide';
            }
            if ($paymentMethod === 'online') {
                if ($cardHolder === '' || strlen($cardHolder) < 3) {
                    $errors['card_holder'] = 'Nom porteur carte invalide';
                }
                if (!preg_match('/^\d{16}$/', (string) $cardNumber)) {
                    $errors['card_number'] = 'Numero de carte invalide (16 chiffres)';
                }
                if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $cardExpiry)) {
                    $errors['card_expiry'] = 'Expiration invalide (MM/YY)';
                }
                if (!preg_match('/^\d{3,4}$/', $cardCvv)) {
                    $errors['card_cvv'] = 'CVV invalide';
                }
            }

            if (empty($errors)) {
                try {
                    // Create orders for each product in cart
                    foreach ($cart as $item) {
                        $order = new Order();
                        $order->setProduct($item['product']);
                        $order->setQuantity($item['quantity']);
                        $order->setOrderDate(new \DateTime());
                        if ($this->getUser()) {
                            $order->setEntraineur($this->getUser());
                        }
                        $order->setContactEmail($email);
                        $order->setShippingAddress(trim($address . ', ' . $city . ' ' . $zipCode));
                        $order->setPaymentMethod($paymentMethod);
                        if ($paymentMethod === 'online') {
                            $order->setPaymentStatus('paid');
                            $order->setStatus('confirmed');
                        } else {
                            $order->setPaymentStatus('pending');
                            $order->setStatus('pending');
                        }
                        $order->setTotalAmount(number_format(
                            ((float) $item['product']->getPrice()) * ((int) $item['quantity']),
                            2,
                            '.',
                            ''
                        ));

                        // For now, we don't have a User association, but you can add one
                        // For demo purposes, we'll use a generic message
                        
                        $entityManager->persist($order);
                    }

                    $entityManager->flush();

                    // Clear cart
                    $cartService->clearCart();

                    // Show success page
                    return $this->render('front_office/checkout/success.html.twig', [
                        'email' => $email,
                        'name' => $name,
                        'orderData' => [
                            'items' => count($cart),
                            'total' => $cartService->getCartTotal(),
                        ]
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la création de la commande: ' . $e->getMessage());
                }
            } else {
                // Display  errors
                foreach ($errors as $field => $error) {
                    $this->addFlash('error', "$field: $error");
                }
            }
        }

        $total = $cartService->getCartTotal();

        return $this->render('front_office/checkout/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
            'cartCount' => $cartService->getCartCount(),
            'errors' => [],
        ]);
    }
}
