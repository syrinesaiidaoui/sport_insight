<?php

namespace App\Controller\FrontOffice;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// security attribute import removed for public/no-login mode

#[Route('/shop', name: 'app_shop_')]
class ShopController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, CartService $cartService): Response
    {
        // Get search and filter parameters
        $searchTerm = trim($request->query->get('search', ''));
        $categoryFilter = $request->query->get('category', '');
        $sortBy = $request->query->get('sort', 'name');
        $sortOrder = $request->query->get('order', 'ASC');

        // Sanitize inputs
        $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');

        // Build query
        $qb = $productRepository->createQueryBuilder('p')
            ->where('p.stock > 0'); // Only show products in stock

        // Apply search
        if ($searchTerm) {
            $qb->andWhere('p.name LIKE :search OR p.category LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Apply category filter
        if ($categoryFilter) {
            $categoryFilter = htmlspecialchars($categoryFilter, ENT_QUOTES, 'UTF-8');
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $categoryFilter);
        }

        // Apply sorting (whitelist)
        $allowedSorts = ['name', 'price', 'stock'];
        if (in_array($sortBy, $allowedSorts)) {
            $qb->orderBy('p.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $products = $qb->getQuery()->getResult();

        // Get unique categories for filter dropdown
        $allProducts = $productRepository->findAll();
        $categories = [];
        foreach ($allProducts as $product) {
            if ($product->getCategory() && !in_array($product->getCategory(), $categories)) {
                $categories[] = $product->getCategory();
            }
        }
        sort($categories);

        return $this->render('front_office/shop/index.html.twig', [
            'products' => $products,
            'searchTerm' => $searchTerm,
            'categoryFilter' => $categoryFilter,
            'categories' => $categories,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'cartCount' => $cartService->getCartCount(),
        ]);
    }

    #[Route('/product/{id}', name: 'product_detail', methods: ['GET'])]
    public function productDetail(int $id, ProductRepository $productRepository, CartService $cartService): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('front_office/shop/product_detail.html.twig', [
            'product' => $product,
            'inCart' => $cartService->isInCart($id),
            'cartQuantity' => $cartService->getQuantityInCart($id),
            'cartCount' => $cartService->getCartCount(),
        ]);
    }

    #[Route('/add-to-cart/{id}', name: 'add_to_cart', methods: ['POST'])]
    public function addToCart(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        CartService $cartService
    ): Response {
        $product = $productRepository->find($id);

        if (!$product) {
            $this->addFlash('error', 'Produit non trouvé');
            return $this->redirectToRoute('app_shop_index');
        }

        // Get quantity from request
        $quantity = max(1, (int)$request->request->get('quantity', 1));

        try {
            $cartService->addToCart($product, $quantity);
            $this->addFlash('success', sprintf('%d x %s ajouté au panier', $quantity, $product->getName()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'ajout au panier');
        }

        $referer = $request->headers->get('referer', $this->generateUrl('app_shop_index'));
        return $this->redirect($referer);
    }
}
