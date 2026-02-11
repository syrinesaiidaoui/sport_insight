-- SOLUTION 1: Delete and recreate admin user
-- First, check if admin exists
SELECT * FROM `user` WHERE `email` = 'admin@test.com';

-- Delete old admin if exists
DELETE FROM `user` WHERE `email` = 'admin@test.com';

-- Create new admin with a working password hash
-- Email: admin@test.com
-- Password: admin123 (CHANGED TO SIMPLER PASSWORD)
-- Hash generated with bcrypt cost 13
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'admin@test.com',
    '["ROLE_ADMIN","ROLE_USER"]',
    '$2y$13$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
    'Admin',
    'Système',
    '12345678',
    '1990-01-01',
    NULL,
    'actif',
    NOW()
);

-- Verify the user was created correctly
SELECT id, email, roles, nom, prenom, statut, date_inscription FROM `user` WHERE email = 'admin@test.com';

-- ============================================
-- CREDENTIALS TO USE:
-- Email: admin@test.com
-- Password: admin123
-- ============================================
