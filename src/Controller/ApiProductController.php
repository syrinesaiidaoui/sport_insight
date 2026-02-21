<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/catalog/products', name: 'api_catalog_products', methods: ['GET'])]
class ApiProductController extends AbstractController
{
    private const FALLBACK_IMAGES = [
        'football_ball.png',
        'football_cleats.png',
        'football_jersey.png',
    ];

    public function __invoke(ProductRepository $productRepository): JsonResponse
    {
        $products = $this->loadProductsFromJson();

        if (empty($products)) {
            $products = $this->loadProductsFromDatabase($productRepository);
        }

        if (empty($products)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No products available in JSON file or database.',
                'count' => 0,
                'products' => [],
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $normalized = $this->normalizeProducts($products);

        return new JsonResponse([
            'success' => true,
            'count' => count($normalized),
            'products' => $normalized,
        ]);
    }

    private function loadProductsFromJson(): array
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/api/products.json';
        if (!is_file($filePath)) {
            return [];
        }

        $raw = file_get_contents($filePath);
        $decoded = json_decode($raw ?: '[]', true);

        return is_array($decoded) ? $decoded : [];
    }

    private function loadProductsFromDatabase(ProductRepository $productRepository): array
    {
        $rows = [];
        foreach ($productRepository->findAll() as $product) {
            $rows[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'category' => $product->getCategory() ?: 'Football',
                'price' => (float)$product->getPrice(),
                'stock' => (int)$product->getStock(),
                'size' => $product->getSize() ?: 'M',
                'brand' => $product->getBrand() ?: 'Generic',
                'image' => $product->getImage(),
                'description' => 'Football equipment item from database catalog.',
            ];
        }

        return $rows;
    }

    private function normalizeProducts(array $products): array
    {
        $normalized = [];

        foreach (array_values($products) as $index => $product) {
            if (!is_array($product)) {
                continue;
            }

            $id = isset($product['id']) ? (int)$product['id'] : ($index + 1);
            $name = trim((string)($product['name'] ?? 'Product ' . $id));
            $image = trim((string)($product['image'] ?? ''));

            if ($image === '') {
                $image = self::FALLBACK_IMAGES[$index % count(self::FALLBACK_IMAGES)];
            }

            $existingImageUrl = trim((string)($product['imageUrl'] ?? ''));
            if ($existingImageUrl !== '') {
                $imageUrl = $existingImageUrl;
            } elseif (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                $imageUrl = $image;
            } elseif (str_starts_with($image, 'api/') || str_starts_with($image, '/api/')) {
                $imageUrl = '/' . ltrim($image, '/');
            } else {
                $imageUrl = '/uploads/' . ltrim($image, '/');
            }

            $normalized[] = [
                'id' => $id,
                'name' => $name,
                'category' => (string)($product['category'] ?? 'Football'),
                'price' => (float)($product['price'] ?? 0),
                'stock' => (int)($product['stock'] ?? 0),
                'size' => (string)($product['size'] ?? 'M'),
                'brand' => (string)($product['brand'] ?? 'Generic'),
                'image' => $image,
                'imageUrl' => $imageUrl,
                'description' => (string)($product['description'] ?? 'Football store item.'),
            ];
        }

        return $normalized;
    }
}
