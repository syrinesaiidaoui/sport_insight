<?php

namespace App\Controller\Api;

use App\Entity\ProductOrder\Product;
use App\Repository\ProductRepository;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products', name: 'api_products_')]
class ProductApiController extends AbstractController
{
    private const FALLBACK_IMAGES = [
        'football_ball.png',
        'football_cleats.png',
        'football_jersey.png',
    ];

    public function __construct(
        private ValidationService $validationService,
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON data',
                    'errors' => ['Invalid request body']
                ], Response::HTTP_BAD_REQUEST);
            }

            $requiredFields = ['name', 'price', 'stock', 'category'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Missing required fields',
                    'errors' => $missingFields
                ], Response::HTTP_BAD_REQUEST);
            }

            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice((string)(float)$data['price']);
            $product->setStock((int)$data['stock']);
            $product->setCategory($data['category']);
            $product->setBrand($data['brand'] ?? '');
            $product->setSize($data['size'] ?? '');
            $product->setImage($data['image'] ?? '');

            $errors = $this->validationService->validateProductData([
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'stock' => $product->getStock(),
                'category' => $product->getCategory(),
            ]);

            if (!empty($errors)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $this->normalizeProductEntity($product, 0)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        try {
            $product = $this->productRepository->find($id);
            if (!$product) {
                return $this->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['name'])) {
                $product->setName($data['name']);
            }
            if (isset($data['price'])) {
                $product->setPrice((string)(float)$data['price']);
            }
            if (isset($data['stock'])) {
                $product->setStock((int)$data['stock']);
            }
            if (isset($data['category'])) {
                $product->setCategory($data['category']);
            }
            if (isset($data['brand'])) {
                $product->setBrand($data['brand']);
            }
            if (isset($data['size'])) {
                $product->setSize($data['size']);
            }
            if (isset($data['image'])) {
                $product->setImage($data['image']);
            }

            $errors = $this->validationService->validateProductData([
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'stock' => $product->getStock(),
                'category' => $product->getCategory(),
            ]);

            if (!empty($errors)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $this->normalizeProductEntity($product, 0)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $product = $this->productRepository->find($id);
            if (!$product) {
                return $this->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($product);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        try {
            $products = $this->productRepository->findAll();
            $data = [];

            foreach ($products as $index => $product) {
                $data[] = $this->normalizeProductEntity($product, $index);
            }

            if (empty($data)) {
                $data = $this->loadProductsFromJson();
            }

            return $this->json([
                'success' => true,
                'count' => count($data),
                'products' => $data
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function loadProductsFromJson(): array
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/api/products.json';
        if (!is_file($filePath)) {
            return [];
        }

        $raw = file_get_contents($filePath);
        $decoded = json_decode($raw ?: '[]', true);
        if (!is_array($decoded)) {
            return [];
        }

        $data = [];
        foreach (array_values($decoded) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $image = trim((string)($item['image'] ?? ''));
            if ($image === '') {
                $image = self::FALLBACK_IMAGES[$index % count(self::FALLBACK_IMAGES)];
            }

            $existingImageUrl = trim((string)($item['imageUrl'] ?? ''));
            if ($existingImageUrl !== '') {
                $imageUrl = $existingImageUrl;
            } elseif (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                $imageUrl = $image;
            } else {
                $imageUrl = '/api/' . ltrim($image, '/');
            }

            $id = isset($item['id']) ? (int)$item['id'] : ($index + 1);

            $data[] = [
                'id' => $id,
                'name' => (string)($item['name'] ?? 'Product ' . $id),
                'price' => (float)($item['price'] ?? 0),
                'stock' => (int)($item['stock'] ?? 0),
                'category' => (string)($item['category'] ?? 'Football'),
                'brand' => (string)($item['brand'] ?? 'Generic'),
                'size' => (string)($item['size'] ?? 'M'),
                'image' => $image,
                'imageUrl' => $imageUrl,
                'description' => (string)($item['description'] ?? 'Football store item.'),
            ];
        }

        return $data;
    }

    private function normalizeProductEntity(Product $product, int $index): array
    {
        $image = trim((string)$product->getImage());
        if ($image === '') {
            $image = self::FALLBACK_IMAGES[$index % count(self::FALLBACK_IMAGES)];
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            $imageUrl = $image;
        } elseif (str_starts_with($image, 'api/') || str_starts_with($image, '/api/')) {
            $imageUrl = '/' . ltrim($image, '/');
        } else {
            $imageUrl = '/uploads/' . ltrim($image, '/');
        }

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => (float)$product->getPrice(),
            'stock' => (int)$product->getStock(),
            'category' => $product->getCategory() ?: 'Football',
            'brand' => $product->getBrand() ?: 'Generic',
            'size' => $product->getSize() ?: 'M',
            'image' => $image,
            'imageUrl' => $imageUrl,
            'description' => 'Football equipment item from database catalog.',
        ];
    }
}
