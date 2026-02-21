<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/catalog/products', name: 'api_catalog_products', methods: ['GET'])]
class ApiProductController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/api/products.json';

        if (!is_file($filePath)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'products.json not found.'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $raw = file_get_contents($filePath);
        $products = json_decode($raw ?: '[]', true);

        if (!is_array($products)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid JSON format in products.json.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'success' => true,
            'count' => count($products),
            'products' => $products,
        ]);
    }
}
