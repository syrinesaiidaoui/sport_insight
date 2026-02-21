<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\ProductOrder\Order;
use App\Entity\ProductOrder\Product;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/equipement')]
class EquipementController extends AbstractController
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    // =========================
    //  Product listing
    // =========================
    #[Route('/', name: 'front_equipement_index')]
    public function index(ProductRepository $repo, Request $request): Response
    {
        $q = $request->query->get('q');
        $category = $request->query->get('category');
        $sort = $request->query->get('sort');

        $products = $repo->searchProducts($q, $category, $sort);
        $categories = $repo->findDistinctCategories();

        $apiProducts = [];
        $apiFilePath = $this->getParameter('kernel.project_dir') . '/public/api/products.json';
        if (is_file($apiFilePath)) {
            $apiRaw = file_get_contents($apiFilePath);
            $apiDecoded = json_decode($apiRaw ?: '[]', true);
            if (is_array($apiDecoded)) {
                $apiProducts = $apiDecoded;
            }
        }

        return $this->render('front_office/equipement/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'apiProducts' => $apiProducts,
            'apiProductsUrl' => $this->generateUrl('api_catalog_products'),
        ]);
    }

    // =========================
    //  Buy product (cart)
    // =========================
    #[Route('/{id}/buy', name: 'front_equipement_buy', requirements: ['id' => '\d+'])]
    public function buy(Product $product, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $id = $product->getId();

        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);

        $this->addFlash('success', 'Produit ajouté au panier.');
        return $this->redirectToRoute('front_equipement_index');
    }

    // =========================
    //  Show product details
    // =========================
    #[Route('/{id}', name: 'front_equipement_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        return $this->render('front_office/equipement/show.html.twig', [
            'product' => $product,
        ]);
    }

    // =========================
    //  Remove from cart
    // =========================
    #[Route('/{id}/remove', name: 'front_equipement_remove', requirements: ['id' => '\d+'])]
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

    // =========================
    //  Cart page
    // =========================
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

    // =========================
    //  Checkout
    // =========================
    #[Route('/checkout', name: 'front_equipement_checkout', methods: ['POST'])]
    public function checkout(Request $request, EntityManagerInterface $em, ProductRepository $repo, MailerInterface $mailer, CsrfTokenManagerInterface $csrfManager): Response
    {
        $user = $this->getUser();
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
            if ($user) {
                $order->setEntraineur($user);
            }

            $product->setStock($product->getStock() - $qty);

            $em->persist($order);
            $em->persist($product);

            $orders[] = $order;
        }

        $em->flush();

        if ($user) {
            $body = $this->renderView('emails/order_confirmation.html.twig', ['user' => $user, 'orders' => $orders]);
            $userEmail = $user->getEmail() ?: $user->getUserIdentifier();
            if ($userEmail) {
                $email = (new Email())
                    ->from('no-reply@sport-insight.local')
                    ->to($userEmail)
                    ->subject('Thank you for your purchase - Sport Insight')
                    ->html($body);

                try { $mailer->send($email); } catch (\Throwable $e) {}
            }
        }

        $session->remove('cart');
        return $this->redirectToRoute('front_equipement_checkout_success');
    }

    #[Route('/checkout-success', name: 'front_equipement_checkout_success')]
    public function checkoutSuccess(): Response
    {
        return $this->render('front_office/equipement/checkout_success.html.twig');
    }

    #[Route('/orders', name: 'front_equipement_orders')]
    public function orders(Request $request, OrderRepository $orderRepo): Response
    {
        $user = $this->getUser();
        $orders = $user ? $user->getOrders() : $orderRepo->findBy([], ['orderDate' => 'DESC'], 50);

        return $this->render('front_office/equipement/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    // =========================
    //  AI Chat endpoint
    // =========================
    #[Route('/ai-chat', name: 'front_equipement_ai_chat', methods: ['POST'])]
    public function aiChat(Request $request, ProductRepository $productRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');
        if (!$userMessage) {
            return new JsonResponse(['reply' => 'Message vide.'], 400);
        }

        // Limit product context to last 50 products to avoid huge requests
        $products = array_slice($productRepo->findAll(), 0, 50);
        $productContext = "";
        foreach ($products as $product) {
            $productContext .= sprintf(
                "Product: %s | Category: %s | Price: %s USD | Stock: %d | Description: %s\n",
                $product->getName(),
                $product->getCategory() ?: 'N/A',
                $product->getPrice(),
                $product->getStock(),
                $product->getDescription() ?: 'No description'
            );
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://api.openai.com/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $_ENV['OPENAI_API_KEY'],
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' =>
"⚡ You are a friendly AI assistant ONLY for the Sport Insight football store.
Answer ONLY questions about products, prices, stock, outfits, or orders.
If a question is unrelated, politely say:
\"Hi! I'm here to help only with Sport Insight products. I can't answer that, but feel free to ask about football items, outfits, stock or prices.\"
Here is the product database:
$productContext"
                            ],
                            [
                                'role' => 'user',
                                'content' => $userMessage
                            ]
                        ],
                        'temperature' => 0.4
                    ]
                ]
            );

            $aiData = $response->toArray();
            $reply = $aiData['choices'][0]['message']['content'] ?? "Sorry, I couldn't generate a reply.";

            return new JsonResponse(['reply' => $reply]);

        } catch (\Throwable $e) {
            // Return real error for debugging
            return new JsonResponse([
                'reply' => 'AI temporarily unavailable: ' . $e->getMessage()
            ], 500);
        }
    }
}
