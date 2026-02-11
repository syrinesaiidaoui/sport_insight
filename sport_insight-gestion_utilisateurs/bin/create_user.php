#!/usr/bin/env php
<?php
// Usage: php bin/create_user.php email@example.com password Prenom Nom
require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

if ($argc < 3) {
    echo "Usage: php bin/create_user.php email password [prenom] [nom]\n";
    exit(1);
}

$email = $argv[1];
$password = $argv[2];
$prenom = $argv[3] ?? 'Admin';
$nom = $argv[4] ?? 'User';

// boot kernel
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

/** @var \Doctrine\ORM\EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();

/** @var \App\Repository\UserRepository $repo */
$repo = $em->getRepository(User::class);

if ($repo->findOneBy(['email' => $email])) {
    echo "User with email $email already exists.\n";
    exit(1);
}

/** @var UserPasswordHasherInterface $hasher */
$hasher = $container->get(UserPasswordHasherInterface::class);

$user = new User();
$user->setEmail($email);
$user->setPrenom($prenom);
$user->setNom($nom);
$user->setRoles(['ROLE_USER']);
$user->setPassword($hasher->hashPassword($user, $password));

$em->persist($user);
$em->flush();

echo "Created user $email (id: " . $user->getId() . ")\n";
