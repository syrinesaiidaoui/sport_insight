<?php
// Quick script to create admin user
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;

// Load environment
$dotenv = new Dotenv();
$dotenv->loadEnv('.env');

// Use Doctrine to create user
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? false));
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

// Check if user exists
$user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'admin@sport.com']);

if (!$user) {
    $user = new \App\Entity\User();
    $user->setEmail('admin@sport.com');
    $user->setNom('Admin');
    $user->setPrenom('Sport');
    $user->setRoles(['ROLE_ADMIN']);
    // Hash password using bcrypt
    $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));
    
    $em->persist($user);
    $em->flush();
    
    echo "✓ Admin user created successfully!\n";
    echo "  Email: admin@sport.com\n";
    echo "  Password: password123\n";
} else {
    echo "✓ Admin user already exists\n";
}

$kernel->shutdown();
