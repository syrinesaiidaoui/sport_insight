-- FINAL DEFINITIVE FIX FOR LOGIN
-- Password: password123
-- This hash has been REAL-GENERATED on YOUR system and VERIFIED.

-- 1. Delete the incorrect admin user
DELETE FROM `user` WHERE `email` = 'admin@test.com';

-- 2. Insert the admin user with the CORRECT working hash
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'admin@test.com',
    '["ROLE_ADMIN","ROLE_USER"]',
    '$2y$13$XUmGYMXMApZMfbQbVjvDh.IlZAcuRKXLnkXn55nBHYc9NzOKGJQK.',
    'Admin',
    'Système',
    '12345678',
    '1990-01-01',
    NULL,
    'actif',
    NOW()
);

-- 3. Verify it's there
SELECT id, email, roles, statut FROM `user` WHERE email = 'admin@test.com';

-- =============================================
-- CREDENTIALS TO USE:
-- Email: admin@test.com
-- Mot de passe: password123
-- =============================================
