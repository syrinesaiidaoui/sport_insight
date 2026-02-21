<?php

namespace App\Command;

use App\Entity\ProductOrder\Order;
use App\Entity\ProductOrder\OrderItem;
use App\Entity\ProductOrder\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed:ecommerce-demo',
    description: 'Generate realistic demo data for users, products, categories and orders.',
)]
class SeedEcommerceDemoDataCommand extends Command
{
    private const STATUSES = [
        'confirmed' => 45,
        'pending' => 35,
        'rejected' => 20,
    ];

    private const CATEGORY_CATALOG = [
        'Jerseys' => ['Nike', 'Adidas', 'Puma', 'New Balance'],
        'Shoes' => ['Nike', 'Adidas', 'Puma', 'Mizuno'],
        'Balls' => ['Adidas', 'Nike', 'Puma', 'Kipsta'],
        'Training' => ['Under Armour', 'Puma', 'Adidas', 'Asics'],
        'Accessories' => ['Nike', 'Adidas', 'Kipsta', 'Umbro'],
    ];

    private const SIZE_BY_CATEGORY = [
        'Jerseys' => ['XS', 'S', 'M', 'L', 'XL'],
        'Shoes' => ['39', '40', '41', '42', '43', '44'],
        'Balls' => ['4', '5'],
        'Training' => ['S', 'M', 'L', 'XL'],
        'Accessories' => ['One'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('users', null, InputOption::VALUE_REQUIRED, 'Number of fake users', '30')
            ->addOption('products', null, InputOption::VALUE_REQUIRED, 'Number of fake products', '45')
            ->addOption('orders', null, InputOption::VALUE_REQUIRED, 'Number of fake orders', '220');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userCount = max(1, (int) $input->getOption('users'));
        $productCount = max(1, (int) $input->getOption('products'));
        $orderCount = max(1, (int) $input->getOption('orders'));

        $io->section('Seeding e-commerce demo data');

        $seedToken = (string) time();
        $users = $this->seedUsers($userCount, $seedToken);
        $products = $this->seedProducts($productCount);
        $statusCount = $this->seedOrders($orderCount, $users, $products);

        $this->entityManager->flush();

        $io->success(sprintf(
            'Seed finished. Users: %d, Products: %d, Orders: %d',
            count($users),
            count($products),
            $orderCount
        ));

        $io->table(
            ['Status', 'Count'],
            [
                ['confirmed', $statusCount['confirmed'] ?? 0],
                ['pending', $statusCount['pending'] ?? 0],
                ['rejected', $statusCount['rejected'] ?? 0],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * @return User[]
     */
    private function seedUsers(int $userCount, string $seedToken): array
    {
        $users = [];

        $admin = new User();
        $admin->setPrenom('Admin');
        $admin->setNom('Dashboard');
        $admin->setEmail(sprintf('admin+%s@sportinsight.local', $seedToken));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setTelephone('+1 555 100 0000');
        $admin->setDateNaissance(new \DateTimeImmutable('1990-01-15'));
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        $this->entityManager->persist($admin);
        $users[] = $admin;

        $firstNames = ['Liam', 'Noah', 'Emma', 'Olivia', 'Mason', 'Lucas', 'Ava', 'Ethan', 'Mia', 'Chloe', 'Sofia', 'Leo', 'Nora', 'Ella'];
        $lastNames = ['Smith', 'Johnson', 'Brown', 'Garcia', 'Miller', 'Wilson', 'Taylor', 'Thomas', 'Martin', 'Anderson', 'Moore', 'White'];

        for ($i = 1; $i <= $userCount; $i++) {
            $firstName = $this->pick($firstNames);
            $lastName = $this->pick($lastNames);

            $user = new User();
            $user->setPrenom($firstName);
            $user->setNom($lastName);
            $user->setEmail(sprintf('customer.%s.%03d@sportinsight.local', strtolower($seedToken), $i));
            $user->setRoles(['ROLE_USER']);
            $user->setTelephone(sprintf('+1 555 %03d %04d', random_int(100, 999), random_int(1000, 9999)));
            $user->setDateNaissance($this->randomBirthDate());
            $user->setPassword($this->passwordHasher->hashPassword($user, 'User123!'));
            $user->setDateInscription($this->randomDateBetween('-18 months', '-2 days'));

            $this->entityManager->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return Product[]
     */
    private function seedProducts(int $productCount): array
    {
        $products = [];
        $productNames = ['Pro', 'Elite', 'Training', 'Match', 'Street', 'Club', 'Performance', 'Classic', 'Power', 'Fusion'];
        $suffixes = ['Edition', 'Series', 'Model', 'Pack', 'Line'];

        for ($i = 1; $i <= $productCount; $i++) {
            $category = $this->pick(array_keys(self::CATEGORY_CATALOG));
            $brand = $this->pick(self::CATEGORY_CATALOG[$category]);
            $size = $this->pick(self::SIZE_BY_CATEGORY[$category]);
            $baseName = sprintf('%s %s %s', $brand, $this->pick($productNames), $category);
            $name = sprintf('%s %s %d', $baseName, $this->pick($suffixes), $i);

            $price = $this->priceByCategory($category);

            $product = new Product();
            $product->setName($name);
            $product->setCategory($category);
            $product->setBrand($brand);
            $product->setSize($size);
            $product->setPrice(number_format($price, 2, '.', ''));
            $product->setStock(random_int(8, 180));
            $product->setImage(null);

            $this->entityManager->persist($product);
            $products[] = $product;
        }

        return $products;
    }

    /**
     * @param User[] $users
     * @param Product[] $products
     * @return array<string, int>
     */
    private function seedOrders(int $orderCount, array $users, array $products): array
    {
        $statusCount = ['confirmed' => 0, 'pending' => 0, 'rejected' => 0];

        for ($i = 0; $i < $orderCount; $i++) {
            $status = $this->weightedStatus();
            $user = $users[random_int(1, count($users) - 1)];
            $product = $products[array_rand($products)];

            $order = new Order();
            $order->setStatus($status);
            $order->setEntraineur($user);
            $order->setOrderDate($this->randomOrderDate());
            $order->setContactEmail($user->getEmail());
            $order->setContactPhone($user->getTelephone());
            $order->setShippingAddress($this->randomAddress());
            $order->setBillingAddress(random_int(1, 100) <= 70 ? $order->getShippingAddress() : $this->randomAddress());
            $paymentMethod = random_int(1, 100) <= 55 ? 'cod' : 'online';
            $order->setPaymentMethod($paymentMethod);

            $lines = random_int(1, 4);
            for ($line = 0; $line < $lines; $line++) {
                $lineProduct = $line === 0 ? $product : $products[array_rand($products)];
                $item = new OrderItem();
                $item->setProduct($lineProduct);
                $item->setQuantity(random_int(1, 4));
                $item->setUnitPrice((string) $lineProduct->getPrice());
                $order->addItem($item);
            }

            $order->syncLegacyProductFieldsFromItems();
            $order->setTotalAmount(number_format($order->getComputedTotal(), 2, '.', ''));
            if ($paymentMethod === 'online') {
                $order->setPaymentStatus($status === 'rejected' ? 'failed' : 'paid');
            } else {
                $order->setPaymentStatus($status === 'confirmed' ? 'paid' : 'pending');
            }

            $this->entityManager->persist($order);
            $statusCount[$status]++;
        }

        return $statusCount;
    }

    private function weightedStatus(): string
    {
        $roll = random_int(1, 100);
        $cursor = 0;

        foreach (self::STATUSES as $status => $weight) {
            $cursor += $weight;
            if ($roll <= $cursor) {
                return $status;
            }
        }

        return 'pending';
    }

    private function randomOrderDate(): \DateTime
    {
        $start = random_int(1, 100) <= 70 ? '-90 days' : '-365 days';
        return $this->randomDateBetween($start, 'now');
    }

    private function randomBirthDate(): \DateTime
    {
        return $this->randomDateBetween('-55 years', '-18 years');
    }

    private function randomDateBetween(string $from, string $to): \DateTime
    {
        $fromDate = new \DateTimeImmutable($from);
        $toDate = new \DateTimeImmutable($to);

        $fromTs = $fromDate->getTimestamp();
        $toTs = $toDate->getTimestamp();
        $randomTs = random_int(min($fromTs, $toTs), max($fromTs, $toTs));

        $date = (new \DateTimeImmutable())->setTimestamp($randomTs)->setTime(0, 0);

        return \DateTime::createFromImmutable($date);
    }

    /**
     * @param array<int, string> $values
     */
    private function pick(array $values): string
    {
        return $values[array_rand($values)];
    }

    private function priceByCategory(string $category): float
    {
        return match ($category) {
            'Jerseys' => random_int(4500, 12000) / 100,
            'Shoes' => random_int(6000, 18000) / 100,
            'Balls' => random_int(1800, 7000) / 100,
            'Training' => random_int(2500, 9500) / 100,
            default => random_int(1200, 6500) / 100,
        };
    }

    private function randomAddress(): string
    {
        $streets = ['Main St', 'Park Ave', 'Market St', 'Elm Street', 'Sunset Blvd', 'Maple Drive'];
        $cities = ['Austin', 'Chicago', 'San Diego', 'Seattle', 'Boston', 'Miami'];
        $states = ['TX', 'IL', 'CA', 'WA', 'MA', 'FL'];

        $cityIdx = array_rand($cities);

        return sprintf(
            '%d %s, %s, %s %05d',
            random_int(100, 9999),
            $streets[array_rand($streets)],
            $cities[$cityIdx],
            $states[$cityIdx],
            random_int(10000, 99999)
        );
    }
}
