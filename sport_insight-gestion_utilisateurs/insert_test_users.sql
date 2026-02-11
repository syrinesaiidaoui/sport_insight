-- Script SQL pour créer des utilisateurs de test
-- Mot de passe pour tous les utilisateurs: "password123"
-- Hash généré avec bcrypt (coût 13)

-- 1. Admin User (ROLE_ADMIN)
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'admin@test.com',
    '["ROLE_ADMIN", "ROLE_USER"]',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin',
    'Système',
    '12345678',
    '1990-01-01',
    NULL,
    'actif',
    NOW()
);

-- 2. Regular User (ROLE_USER)
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'user@test.com',
    '["ROLE_USER"]',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Utilisateur',
    'Test',
    '87654321',
    '1995-05-15',
    NULL,
    'actif',
    NOW()
);

-- 3. Entraineur User
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'entraineur@test.com',
    '["ROLE_USER"]',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Dupont',
    'Jean',
    '11223344',
    '1985-03-20',
    NULL,
    'actif',
    NOW()
);

-- 4. Joueur User
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'joueur@test.com',
    '["ROLE_USER"]',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Martin',
    'Pierre',
    '55667788',
    '2000-07-10',
    NULL,
    'actif',
    NOW()
);

-- 5. Blocked User (pour tester le statut bloqué)
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'blocked@test.com',
    '["ROLE_USER"]',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Bloqué',
    'Utilisateur',
    '99887766',
    '1992-12-25',
    NULL,
    'bloque',
    NOW()
);

-- INFORMATIONS DE CONNEXION:
-- ============================
-- Email: admin@test.com
-- Mot de passe: password123
-- Rôle: ADMIN
--
-- Email: user@test.com
-- Mot de passe: password123
-- Rôle: USER
--
-- Email: entraineur@test.com
-- Mot de passe: password123
-- Rôle: USER
--
-- Email: joueur@test.com
-- Mot de passe: password123
-- Rôle: USER
--
-- Email: blocked@test.com
-- Mot de passe: password123
-- Rôle: USER (Statut: bloqué)
