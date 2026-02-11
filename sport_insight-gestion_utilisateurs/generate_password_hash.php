<?php
// generate_password_hash.php
// Run this file to generate a valid password hash for Symfony

// Password to hash
$password = 'admin123';

// Generate hash using PASSWORD_BCRYPT with cost 13 (matching security.yaml)
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 13]);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\n";
echo "Copy this SQL and run it in phpMyAdmin:\n\n";
echo "DELETE FROM `user` WHERE `email` = 'admin@test.com';\n\n";
echo "INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) \n";
echo "VALUES (\n";
echo "    'admin@test.com',\n";
echo "    '[\"ROLE_ADMIN\",\"ROLE_USER\"]',\n";
echo "    '$hash',\n";
echo "    'Admin',\n";
echo "    'Système',\n";
echo "    '12345678',\n";
echo "    '1990-01-01',\n";
echo "    NULL,\n";
echo "    'actif',\n";
echo "    NOW()\n";
echo ");\n";
