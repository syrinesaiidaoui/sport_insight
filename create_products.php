<?php
// Quick script to create sample products
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment
$dotenv = new Dotenv();
$dotenv->loadEnv('.env');

$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? false));
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$products = [
    ['name' => 'Maillots domicile 2025/26', 'category' => 'Tenue', 'price' => 45.00, 'stock' => 50, 'size' => 'M-XXL', 'brand' => 'Nike'],
    ['name' => 'Maillots exterieur 2025/26', 'category' => 'Tenue', 'price' => 45.00, 'stock' => 40, 'size' => 'M-XXL', 'brand' => 'Nike'],
    ['name' => 'Short officiel', 'category' => 'Tenue', 'price' => 25.00, 'stock' => 60, 'size' => 'M-XXL', 'brand' => 'Nike'],
    ['name' => 'Ballons entrainement', 'category' => 'Materiel', 'price' => 35.00, 'stock' => 8, 'size' => null, 'brand' => 'Adidas'],
    ['name' => 'Cones et plots', 'category' => 'Materiel', 'price' => 12.00, 'stock' => 100, 'size' => null, 'brand' => 'Puma'],
    ['name' => 'Chaussures Adidas Predator', 'category' => 'Chaussures', 'price' => 120.00, 'stock' => 3, 'size' => '39-46', 'brand' => 'Adidas'],
    ['name' => 'Gants de gardien', 'category' => 'Equipement', 'price' => 55.00, 'stock' => 25, 'size' => '8-12', 'brand' => 'Puma'],
    ['name' => 'Protege-tibias', 'category' => 'Equipement', 'price' => 15.00, 'stock' => 80, 'size' => 'S-L', 'brand' => 'Nike'],
];

foreach ($products as $data) {
    $existing = $em->getRepository(\App\Entity\Product::class)->findOneBy(['name' => $data['name']]);
    if (!$existing) {
        $product = new \App\Entity\Product();
        $product->setName($data['name']);
        $product->setCategory($data['category']);
        $product->setPrice($data['price']);
        $product->setStock($data['stock']);
        $product->setSize($data['size']);
        $product->setBrand($data['brand']);
        $product->setImage(null);
        
        $em->persist($product);
        echo "✓ Added: {$data['name']}\n";
    }
}

$em->flush();
echo "\n✓ All sample products created!\n";

$kernel->shutdown();
