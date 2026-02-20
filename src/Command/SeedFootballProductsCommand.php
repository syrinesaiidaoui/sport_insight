<?php

namespace App\Command;

use App\Entity\ProductOrder\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:football-products',
    description: 'Seed the database with football products',
)]
class SeedFootballProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $footballProducts = [
            // Club jerseys (real teams)
            [
                'name' => 'Real Madrid Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 120,
                'category' => 'Maillots',
                'brand' => 'Adidas',
                'size' => 'M',
                'image' => 'real_madrid_home_2324.jpg',
            ],
            [
                'name' => 'FC Barcelona Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 110,
                'category' => 'Maillots',
                'brand' => 'Nike',
                'size' => 'M',
                'image' => 'barcelona_home_2324.jpg',
            ],
            [
                'name' => 'Manchester United Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 100,
                'category' => 'Maillots',
                'brand' => 'Adidas',
                'size' => 'L',
                'image' => 'manutd_home_2324.jpg',
            ],
            [
                'name' => 'Liverpool FC Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 95,
                'category' => 'Maillots',
                'brand' => 'Nike',
                'size' => 'M',
                'image' => 'liverpool_home_2324.jpg',
            ],
            [
                'name' => 'Bayern Munich Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 80,
                'category' => 'Maillots',
                'brand' => 'Adidas',
                'size' => 'M',
                'image' => 'bayern_home_2324.jpg',
            ],
            [
                'name' => 'Paris Saint-Germain Home Jersey 23/24',
                'price' => 84.99,
                'stock' => 90,
                'category' => 'Maillots',
                'brand' => 'Jordan',
                'size' => 'M',
                'image' => 'psg_home_2324.jpg',
            ],
            [
                'name' => 'Juventus Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 70,
                'category' => 'Maillots',
                'brand' => 'Adidas',
                'size' => 'L',
                'image' => 'juventus_home_2324.jpg',
            ],
            [
                'name' => 'AC Milan Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 65,
                'category' => 'Maillots',
                'brand' => 'Puma',
                'size' => 'M',
                'image' => 'acmilan_home_2324.jpg',
            ],
            [
                'name' => 'Chelsea FC Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 75,
                'category' => 'Maillots',
                'brand' => 'Nike',
                'size' => 'M',
                'image' => 'chelsea_home_2324.jpg',
            ],
            [
                'name' => 'Arsenal FC Home Jersey 23/24',
                'price' => 79.99,
                'stock' => 85,
                'category' => 'Maillots',
                'brand' => 'Adidas',
                'size' => 'M',
                'image' => 'arsenal_home_2324.jpg',
            ],
            // National team jerseys
            [
                'name' => 'Brazil National Team Home Jersey 2024',
                'price' => 74.99,
                'stock' => 120,
                'category' => 'Maillots',
                'brand' => 'Nike',
                'size' => 'M',
                'image' => 'brazil_home_2024.jpg',
            ],
            [
                'name' => 'Argentina National Team Home Jersey 2024',
                'price' => 74.99,
                'stock' => 110,
                'category' => 'Maillots',
                'brand' => 'Adidas',
                'size' => 'M',
                'image' => 'argentina_home_2024.jpg',
            ],
            [
                'name' => 'Spain National Team Home Jersey 2024',
                'price' => 69.99,
                'stock' => 95,
                'category' => 'Maillots',
                'brand' => 'Adidas',
                'size' => 'M',
                'image' => 'spain_home_2024.jpg',
            ],
            [
                'name' => 'Germany National Team Home Jersey 2024',
                'price' => 69.99,
                'stock' => 90,
                'category' => 'Maillots',
                'brand' => 'Adidas',
                'size' => 'M',
                'image' => 'germany_home_2024.jpg',
            ],
            [
                'name' => 'England National Team Home Jersey 2024',
                'price' => 69.99,
                'stock' => 100,
                'category' => 'Maillots',
                'brand' => 'Nike',
                'size' => 'M',
                'image' => 'england_home_2024.jpg',
            ],
            // keep one popular accessory
            [
                'name' => 'Official Match Ball 2024',
                'price' => 59.99,
                'stock' => 50,
                'category' => 'Ballons',
                'brand' => 'Adidas',
                'size' => '5',
                'image' => 'official_match_ball_2024.jpg',
            ],
        ];

        $io->section('🏈 Seeding Football Products');

        $count = 0;
        foreach ($footballProducts as $productData) {
            $existingProduct = $this->entityManager->getRepository(Product::class)
                ->findOneBy(['name' => $productData['name']]);

            if ($existingProduct) {
                $io->note("Product already exists: {$productData['name']}");
                continue;
            }

            $product = new Product();
            $product->setName($productData['name']);
            $product->setPrice($productData['price']);
            $product->setStock($productData['stock']);
            $product->setCategory($productData['category']);
            $product->setBrand($productData['brand']);
            $product->setSize($productData['size']);
            $product->setImage($productData['image']);

            $this->entityManager->persist($product);
            $count++;

            $io->writeln("✅ Added: <info>{$productData['name']}</info> - \${$productData['price']} ({$productData['stock']} stock)");
        }

        $this->entityManager->flush();

        $io->success("🎉 Successfully seeded {$count} football products!");

        return Command::SUCCESS;
    }
}
