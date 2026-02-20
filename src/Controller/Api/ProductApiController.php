<?php

namespace App\Controller\Api;

use App\Entity\ProductOrder\Product;
use App\Repository\ProductRepository;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// security attribute import removed for public/no-login mode

#[Route('/api/products', name: 'api_products_')]
class ProductApiController extends AbstractController
{
    public function __construct(
        private ValidationService $validationService,
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        try {
            // Get JSON data
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON data',
                    'errors' => ['Invalid request body']
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
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

            // Create product entity
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice((float)$data['price']);
            $product->setStock((int)$data['stock']);
            $product->setCategory($data['category']);
            $product->setBrand($data['brand'] ?? '');
            $product->setSize($data['size'] ?? '');
            $product->setImage($data['image'] ?? '');

            // Validate product
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

            // Save product
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'stock' => $product->getStock(),
                    'category' => $product->getCategory(),
                    'brand' => $product->getBrand(),
                    'size' => $product->getSize(),
                ]
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

            if (isset($data['name'])) $product->setName($data['name']);
            if (isset($data['price'])) $product->setPrice((float)$data['price']);
            if (isset($data['stock'])) $product->setStock((int)$data['stock']);
            if (isset($data['category'])) $product->setCategory($data['category']);
            if (isset($data['brand'])) $product->setBrand($data['brand']);
            if (isset($data['size'])) $product->setSize($data['size']);
            if (isset($data['image'])) $product->setImage($data['image']);

            // Validate
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
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'stock' => $product->getStock(),
                    'category' => $product->getCategory(),
                ]
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
    public function list(Request $request): Response
    {
        try {
            $products = $this->productRepository->findAll();

            $data = [];
            foreach ($products as $product) {
                $data[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'stock' => $product->getStock(),
                    'category' => $product->getCategory(),
                    'brand' => $product->getBrand(),
                    'size' => $product->getSize(),
                    'image' => $product->getImage(),
                ];
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
}
