<?php
// Quick script to create a regular user
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv('.env');

$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? false));
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'user@sport.com']);

if (!$user) {
    $user = new \App\Entity\User();
    $user->setEmail('user@sport.com');
    $user->setNom('User');
    $user->setPrenom('Sport');
    $user->setRoles(['ROLE_USER']);
    // use bcrypt
    $user->setPassword(password_hash('userpass', PASSWORD_BCRYPT));

    $em->persist($user);
    $em->flush();

    echo "✓ User created: user@sport.com / userpass\n";
} else {
    echo "User already exists\n";
}

// Also ensure the requested test user exists
$amine = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'Amine.bouchnak@esprit.tn']);

if (!$amine) {
    $amine = new \App\Entity\User();
    $amine->setEmail('Amine.bouchnak@esprit.tn');
    $amine->setNom('Bouchnak');
    $amine->setPrenom('Amine');
    $amine->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
    $amine->setPassword(password_hash('Amine123', PASSWORD_BCRYPT));

    $em->persist($amine);
    $em->flush();

    echo "✓ Test user created: Amine.bouchnak@esprit.tn / Amine123\n";
} else {
    // Update existing user to have ROLE_ADMIN
    $amine->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
    $em->flush();
    echo "✓ Test user already exists: Amine.bouchnak@esprit.tn (updated with ROLE_ADMIN)\n";
}

$kernel->shutdown();
