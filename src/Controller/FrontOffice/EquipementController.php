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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
    public function index(ProductRepository $repo, Request $request, EntityManagerInterface $em): Response
    {
        $this->syncJsonCatalogToDatabase($repo, $em);

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
                foreach ($apiProducts as &$apiProduct) {
                    if (!is_array($apiProduct)) {
                        continue;
                    }
                    $name = trim((string)($apiProduct['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $dbProduct = $repo->findOneBy(['name' => $name]);
                    if ($dbProduct) {
                        $apiProduct['dbId'] = $dbProduct->getId();
                    }
                }
                unset($apiProduct);
            }
        }

        return $this->render('front_office/equipement/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'apiProducts' => $apiProducts,
            'apiProductsUrl' => $this->generateUrl('api_catalog_products'),
        ]);
    }

    private function syncJsonCatalogToDatabase(ProductRepository $repo, EntityManagerInterface $em): void
    {
        $apiFilePath = $this->getParameter('kernel.project_dir') . '/public/api/products.json';
        if (!is_file($apiFilePath)) {
            return;
        }

        $apiRaw = file_get_contents($apiFilePath);
        $apiDecoded = json_decode($apiRaw ?: '[]', true);
        if (!is_array($apiDecoded)) {
            return;
        }

        $hasChanges = false;

        foreach ($apiDecoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string)($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $product = $repo->findOneBy(['name' => $name]);
            if (!$product) {
                $product = new Product();
                $product->setName($name);
                $em->persist($product);
            }

            $product->setCategory((string)($item['category'] ?? 'Football'));
            $product->setPrice((string)((float)($item['price'] ?? 0)));
            $product->setStock((int)($item['stock'] ?? 0));
            $product->setSize((string)($item['size'] ?? 'M'));
            $product->setBrand((string)($item['brand'] ?? 'Generique'));
            $image = trim((string)($item['image'] ?? ''));
            if ($image !== '' && !str_starts_with($image, 'http://') && !str_starts_with($image, 'https://') && !str_starts_with($image, 'api/') && !str_starts_with($image, '/api/')) {
                $image = 'api/' . ltrim($image, '/');
            }
            $product->setImage($image !== '' ? $image : null);

            $hasChanges = true;
        }

        if ($hasChanges) {
            $em->flush();
        }
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

        $this->addFlash('success', 'Produit ajoute au panier.');
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
            $this->addFlash('success', 'Produit retire du panier.');
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
    #[Route('/checkout', name: 'front_equipement_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, EntityManagerInterface $em, ProductRepository $repo, MailerInterface $mailer, CsrfTokenManagerInterface $csrfManager): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('front_equipement_index');
        }

        $items = [];
        $total = 0.0;
        foreach ($cart as $id => $qty) {
            $product = $repo->find($id);
            if (!$product) {
                continue;
            }
            $lineTotal = floatval($product->getPrice()) * $qty;
            $items[] = ['product' => $product, 'quantity' => $qty, 'lineTotal' => $lineTotal];
            $total += $lineTotal;
        }

        if ($request->isMethod('GET')) {
            return $this->render('front_office/equipement/checkout.html.twig', [
                'items' => $items,
                'total' => $total,
            ]);
        }

        $token = new CsrfToken('checkout', $request->request->get('_token'));
        if (!$csrfManager->isTokenValid($token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('front_equipement_checkout');
        }

        $fullName = trim((string)$request->request->get('full_name', ''));
        $emailInput = trim((string)$request->request->get('email', ''));
        $phone = trim((string)$request->request->get('phone', ''));
        $address = trim((string)$request->request->get('address', ''));
        $city = trim((string)$request->request->get('city', ''));
        $postalCode = trim((string)$request->request->get('postal_code', ''));
        $paymentMethod = (string) $request->request->get('payment_method', 'cod');
        $cardHolder = trim((string) $request->request->get('card_holder', ''));
        $cardNumber = preg_replace('/\s+/', '', (string) $request->request->get('card_number', ''));
        $cardExpiry = trim((string) $request->request->get('card_expiry', ''));
        $cardCvv = trim((string) $request->request->get('card_cvv', ''));

        if ($fullName === '' || $emailInput === '' || $phone === '' || $address === '' || $city === '' || $postalCode === '') {
            $this->addFlash('danger', 'Merci de renseigner toutes les informations client.');
            return $this->redirectToRoute('front_equipement_checkout');
        }

        if (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('danger', 'Adresse email invalide.');
            return $this->redirectToRoute('front_equipement_checkout');
        }
        if (!in_array($paymentMethod, ['cod', 'online'], true)) {
            $this->addFlash('danger', 'Mode de paiement invalide.');
            return $this->redirectToRoute('front_equipement_checkout');
        }
        if ($paymentMethod === 'online') {
            if ($cardHolder === '' || strlen($cardHolder) < 3) {
                $this->addFlash('danger', 'Nom du porteur de carte invalide.');
                return $this->redirectToRoute('front_equipement_checkout');
            }
            if (!preg_match('/^\d{16}$/', (string) $cardNumber)) {
                $this->addFlash('danger', 'Numero de carte invalide (16 chiffres).');
                return $this->redirectToRoute('front_equipement_checkout');
            }
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $cardExpiry)) {
                $this->addFlash('danger', 'Date d\'expiration invalide (MM/YY).');
                return $this->redirectToRoute('front_equipement_checkout');
            }
            if (!preg_match('/^\d{3,4}$/', $cardCvv)) {
                $this->addFlash('danger', 'CVV invalide.');
                return $this->redirectToRoute('front_equipement_checkout');
            }
        }

        $user = $this->getUser();
        $orders = [];
        $invoiceLines = [];
        foreach ($cart as $id => $qty) {
            $product = $repo->find($id);
            if (!$product) {
                continue;
            }
            if ($product->getStock() < $qty) {
                $this->addFlash('danger', sprintf('Stock insuffisant pour %s', $product->getName()));
                return $this->redirectToRoute('front_equipement_cart');
            }

            $order = new Order();
            $order->setProduct($product);
            $order->setQuantity($qty);
            $order->setOrderDate(new \DateTime());
            $shippingAddress = trim($address . ', ' . $city . ' ' . $postalCode);
            $order->setContactEmail($emailInput);
            $order->setContactPhone($phone);
            $order->setShippingAddress($shippingAddress);
            $order->setBillingAddress($shippingAddress);
            $order->setPaymentMethod($paymentMethod);
            $order->setTotalAmount(number_format((float)$lineTotal, 2, '.', ''));
            if ($paymentMethod === 'online') {
                $order->setPaymentStatus('paid');
                $order->setStatus('confirmed');
            } else {
                $order->setPaymentStatus('pending');
                $order->setStatus('pending');
            }
            if ($user) {
                $order->setEntraineur($user);
            }

            $product->setStock($product->getStock() - $qty);

            $em->persist($order);
            $em->persist($product);

            $orders[] = $order;
            $invoiceLines[] = sprintf(
                "%s | Quantite: %d | PU: %.2f USD | Total: %.2f USD",
                (string)$product->getName(),
                (int)$qty,
                (float)$product->getPrice(),
                (float)$product->getPrice() * (int)$qty
            );
        }

        $em->flush();

        $invoiceNumber = 'SI-' . date('Ymd-His');
        $invoiceText = $this->buildInvoiceText(
            $invoiceNumber,
            $fullName,
            $emailInput,
            $phone,
            $address,
            $city,
            $postalCode,
            $invoiceLines,
            $total
        );

        $session->set('invoice_text', $invoiceText);
        $session->set('invoice_filename', sprintf('facture-%s.txt', strtolower($invoiceNumber)));

        $emailBody = nl2br($invoiceText);
        $email = (new Email())
            ->from('no-reply@sport-insight.local')
            ->to($emailInput)
            ->subject('Confirmation de commande - Sport Insight')
            ->html('<h2>Merci pour votre commande</h2><p>Voici votre recapitulatif :</p><pre>' . $emailBody . '</pre>');

        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->addFlash('warning', "Commande validee, mais l'email n'a pas pu etre envoye.");
        }

        $session->remove('cart');
        return $this->redirectToRoute('front_equipement_checkout_success');
    }

    #[Route('/checkout-success', name: 'front_equipement_checkout_success')]
    public function checkoutSuccess(): Response
    {
        return $this->render('front_office/equipement/checkout_success.html.twig');
    }

    #[Route('/invoice/download', name: 'front_equipement_invoice_download', methods: ['GET'])]
    public function downloadInvoice(Request $request): Response
    {
        $session = $request->getSession();
        $invoiceText = (string)$session->get('invoice_text', '');
        $filename = (string)$session->get('invoice_filename', 'facture-sport-insight.txt');

        if ($invoiceText === '') {
            $this->addFlash('warning', 'Aucune facture disponible au telechargement.');
            return $this->redirectToRoute('front_equipement_orders');
        }

        $response = new Response($invoiceText);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
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
        $userMessage = trim((string)($data['message'] ?? ''));
        if ($userMessage === '') {
            return new JsonResponse(['reply' => 'Veuillez saisir un message.'], 400);
        }

        $catalog = $this->buildStoreCatalog($productRepo);
        if (empty($catalog)) {
            return new JsonResponse([
                'reply' => 'Je ne peux pas acceder au catalogue pour le moment. Veuillez reessayer dans un instant.'
            ], 500);
        }

        if (!$this->isStoreRelatedMessage($userMessage, $catalog)) {
            return new JsonResponse([
                'reply' => "Bonjour ! Je peux vous aider uniquement sur les produits Sport Insight. Posez-moi une question sur les articles football, les tenues, le stock ou les prix."
            ]);
        }

        $apiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
        if (!$apiKey || $apiKey === 'your_openai_api_key_here') {
            return new JsonResponse([
                'reply' => 'IA non configuree pour le moment. Ajoutez une cle OPENAI_API_KEY valide dans votre environnement local.'
            ], 500);
        }

        $catalogContext = $this->formatCatalogForPrompt($catalog);
        $systemPrompt = <<<PROMPT
Tu es un assistant de vente Sport Insight.
Tu dois parler uniquement des produits et des commandes de cette boutique.

Regles:
- Recommande des produits selon le budget, l'usage, la taille et le stock.
- Mentionne le prix et le stock dans chaque recommandation.
- Si l'utilisateur demande un critere indisponible, propose les options les plus proches du catalogue.
- Si l'utilisateur parle de tshirt/tee/shirt, associe cela aux produits type maillot et pose une courte question de suivi sur la taille/couleur.
- N'invente jamais de produits absents du catalogue.
- Si la question est hors contexte boutique, reponds exactement:
"Bonjour ! Je peux vous aider uniquement sur les produits Sport Insight. Posez-moi une question sur les articles football, les tenues, le stock ou les prix."

Catalogue:
{$catalogContext}
PROMPT;

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://api.openai.com/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $systemPrompt,
                            ],
                            [
                                'role' => 'user',
                                'content' => $userMessage,
                            ],
                        ],
                        'temperature' => 0.5,
                        'max_tokens' => 280,
                    ],
                ]
            );

            $aiData = $response->toArray(false);
            if (isset($aiData['error']['message'])) {
                return new JsonResponse([
                    'reply' => $this->buildFallbackRecommendation($userMessage, $catalog)
                ]);
            }

            $reply = trim((string)($aiData['choices'][0]['message']['content'] ?? ''));
            if ($reply === '') {
                $reply = $this->buildFallbackRecommendation($userMessage, $catalog);
            }

            return new JsonResponse(['reply' => $reply]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'reply' => $this->buildFallbackRecommendation($userMessage, $catalog)
            ]);
        }
    }

    private function buildStoreCatalog(ProductRepository $productRepo): array
    {
        $catalog = [];
        $knownNames = [];

        $products = array_slice($productRepo->findAll(), 0, 80);
        foreach ($products as $product) {
            $name = (string)$product->getName();
            if ($name === '') {
                continue;
            }

            $nameKey = mb_strtolower($name);
            $knownNames[$nameKey] = true;

            $catalog[] = [
                'name' => $name,
                'category' => $product->getCategory() ?: 'N/A',
                'price' => (float)$product->getPrice(),
                'stock' => (int)$product->getStock(),
                'size' => $product->getSize() ?: 'N/A',
                'brand' => $product->getBrand() ?: 'N/A',
                'description' => $product->getDescription() ?: 'Aucune description',
            ];
        }

        $apiFilePath = $this->getParameter('kernel.project_dir') . '/public/api/products.json';
        if (is_file($apiFilePath)) {
            $apiRaw = file_get_contents($apiFilePath);
            $apiDecoded = json_decode($apiRaw ?: '[]', true);

            if (is_array($apiDecoded)) {
                foreach ($apiDecoded as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $name = trim((string)($item['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }

                    $nameKey = mb_strtolower($name);
                    if (isset($knownNames[$nameKey])) {
                        continue;
                    }
                    $knownNames[$nameKey] = true;

                    $catalog[] = [
                        'name' => $name,
                        'category' => (string)($item['category'] ?? 'N/A'),
                        'price' => (float)($item['price'] ?? 0),
                        'stock' => (int)($item['stock'] ?? 0),
                        'size' => (string)($item['size'] ?? 'N/A'),
                        'brand' => (string)($item['brand'] ?? 'N/A'),
                        'description' => (string)($item['description'] ?? 'Aucune description'),
                    ];
                }
            }
        }

        return $catalog;
    }

    private function formatCatalogForPrompt(array $catalog): string
    {
        $lines = [];
        foreach ($catalog as $product) {
            $lines[] = sprintf(
                '- %s | categorie: %s | prix: %.2f USD | stock: %d | marque: %s | taille: %s | description: %s',
                (string)($product['name'] ?? 'N/A'),
                (string)($product['category'] ?? 'N/A'),
                (float)($product['price'] ?? 0),
                (int)($product['stock'] ?? 0),
                (string)($product['brand'] ?? 'N/A'),
                (string)($product['size'] ?? 'N/A'),
                (string)($product['description'] ?? 'Aucune description')
            );
        }

        return implode("\n", $lines);
    }

    private function isStoreRelatedMessage(string $message, array $catalog): bool
    {
        $normalized = mb_strtolower($message);
        $keywords = [
            'football', 'soccer', 'product', 'products', 'equipement', 'equipment',
            'ball', 'boots', 'cleats', 'jersey', 'maillot', 'shirt', 'tshirt', 't-shirt', 'tee',
            'kit', 'outfit', 'color', 'couleur', 'price', 'prix', 'stock',
            'size', 'taille', 'brand', 'marque', 'buy', 'order', 'commande', 'cart', 'panier',
            'recommend', 'recommand', 'promo', 'sale'
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        foreach ($catalog as $product) {
            $name = mb_strtolower((string)($product['name'] ?? ''));
            if ($name !== '' && str_contains($normalized, $name)) {
                return true;
            }
        }

        return false;
    }

    private function buildFallbackRecommendation(string $message, array $catalog): string
    {
        $normalized = mb_strtolower($message);
        $wantsShirt = str_contains($normalized, 'tshirt')
            || str_contains($normalized, 't-shirt')
            || str_contains($normalized, 'shirt')
            || str_contains($normalized, 'tee')
            || str_contains($normalized, 'jersey')
            || str_contains($normalized, 'maillot');

        $inStock = array_values(array_filter($catalog, static function (array $product): bool {
            return ((int)($product['stock'] ?? 0)) > 0;
        }));

        if (empty($inStock)) {
            return "Nous n'avons actuellement aucun article en stock. Je peux quand meme vous aider a parcourir les categories.";
        }

        if ($wantsShirt) {
            $shirtLike = array_values(array_filter($inStock, static function (array $product): bool {
                $name = mb_strtolower((string)($product['name'] ?? ''));
                $category = mb_strtolower((string)($product['category'] ?? ''));
                return str_contains($name, 'jersey')
                    || str_contains($name, 'shirt')
                    || str_contains($name, 'tshirt')
                    || str_contains($name, 't-shirt')
                    || str_contains($name, 'maillot')
                    || str_contains($category, 'jersey')
                    || str_contains($category, 'shirt');
            }));

            $candidates = !empty($shirtLike) ? $shirtLike : $inStock;
            $top = array_slice($candidates, 0, 3);

            $lines = [];
            foreach ($top as $product) {
                $lines[] = sprintf(
                    "- %s (%s USD, stock: %d, taille: %s)",
                    (string)($product['name'] ?? 'Produit'),
                    number_format((float)($product['price'] ?? 0), 2, '.', ''),
                    (int)($product['stock'] ?? 0),
                    (string)($product['size'] ?? 'N/A')
                );
            }

            return "Tres bon choix. Pour un style tshirt/maillot, je recommande :\n"
                . implode("\n", $lines)
                . "\nQuelle taille et quelle couleur preferez-vous ?";
        }

        $top = array_slice($inStock, 0, 3);
        $lines = [];
        foreach ($top as $product) {
            $lines[] = sprintf(
                "- %s (%s USD, stock: %d)",
                (string)($product['name'] ?? 'Produit'),
                number_format((float)($product['price'] ?? 0), 2, '.', ''),
                (int)($product['stock'] ?? 0)
            );
        }

        return "Voici les produits populaires disponibles actuellement :\n"
            . implode("\n", $lines)
            . "\nIndiquez votre budget ou categorie preferee et je vais affiner la selection.";
    }

    private function buildInvoiceText(
        string $invoiceNumber,
        string $fullName,
        string $email,
        string $phone,
        string $address,
        string $city,
        string $postalCode,
        array $invoiceLines,
        float $total
    ): string {
        $header = [
            'SPORT INSIGHT - FACTURE',
            'Numero: ' . $invoiceNumber,
            'Date: ' . date('Y-m-d H:i:s'),
            '',
            'Informations client',
            'Nom: ' . $fullName,
            'Email: ' . $email,
            'Telephone: ' . $phone,
            'Adresse: ' . $address . ', ' . $city . ' ' . $postalCode,
            '',
            'Produits commandes',
        ];

        $footer = [
            '',
            sprintf('TOTAL: %.2f USD', $total),
            '',
            'Merci pour votre commande Sport Insight.',
        ];

        return implode("\n", array_merge($header, $invoiceLines, $footer));
    }
}
