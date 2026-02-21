<?php

namespace App\Command;

use App\Entity\ProductOrder\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:images:assign-api',
    description: 'Assign images from public/api to products using filename-to-product-name matching.'
)]
class AssignApiImagesCommand extends Command
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apiDir = dirname(__DIR__, 2) . '/public/api';
        if (!is_dir($apiDir)) {
            $io->error('Directory public/api not found.');
            return Command::FAILURE;
        }

        $products = $this->productRepository->findAll();
        $nameMap = [];
        foreach ($products as $product) {
            $nameMap[$this->normalize($product->getName() ?? '')][] = $product;
        }

        $files = array_values(array_filter(scandir($apiDir), static function ($file): bool {
            return (bool) preg_match('/^\d+_.+\.(jpg|jpeg|png|webp)$/i', $file);
        }));

        $placeholders = ['api/football_jersey.png', 'api/football_ball.png', 'api/football_cleats.png', 'products/demo-product.png', '', null];
        $updated = 0;
        $unmatched = [];

        foreach ($files as $file) {
            if (!preg_match('/^(\d+)_(.+)\.(jpg|jpeg|png|webp)$/i', $file, $matches)) {
                continue;
            }

            $prefixId = (int) $matches[1];
            $candidateName = $this->normalize((string) $matches[2]);
            $targetProduct = $this->productRepository->find($prefixId);

            if (!$targetProduct) {
                $candidateName = $this->normalize((string) $matches[2]);
                $targets = $nameMap[$candidateName] ?? [];
                if (empty($targets)) {
                    $unmatched[] = $file;
                    continue;
                }

                $targetProduct = $targets[0];
                foreach ($targets as $target) {
                    if (in_array($target->getImage(), $placeholders, true)) {
                        $targetProduct = $target;
                        break;
                    }
                }
            }

            $newImage = 'api/' . $file;
            if ($targetProduct->getImage() !== $newImage) {
                $targetProduct->setImage($newImage);
                $updated++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Updated %d product image(s).', $updated));
        if (!empty($unmatched)) {
            $io->warning(sprintf('%d file(s) were not matched to any product name.', count($unmatched)));
            foreach (array_slice($unmatched, 0, 20) as $file) {
                $io->writeln('- ' . $file);
            }
        }

        return Command::SUCCESS;
    }

    private function normalize(string $text): string
    {
        $text = strtolower($text);
        $text = str_replace(['_', '-', '.'], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = preg_replace('/[^a-z0-9 ]/', '', $text) ?? $text;

        return trim($text);
    }
}
