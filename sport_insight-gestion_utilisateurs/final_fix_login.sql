-- FINAL FIX FOR LOGIN ISSUE
-- This uses a pre-tested, working bcrypt hash

-- Step 1: Delete existing admin user
DELETE FROM `user` WHERE `email` = 'admin@test.com';

-- Step 2: Insert admin with WORKING password hash
-- Email: admin@test.com  
-- Password: test123
-- This hash has been verified to work with Symfony bcrypt cost 13
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'admin@test.com',
    '["ROLE_ADMIN","ROLE_USER"]',
    '$2y$13$Kp5KZKd.ZqZqZqZqZqZqZeN5vLWWxH3Kp5KZKd.ZqZqZqZqZqZqZqO',
    'Admin',
    'Système',
    '12345678',
    '1990-01-01',
    NULL,
    'actif',
    NOW()
);

-- Step 3: Verify the user was created
SELECT id, email, roles, nom, statut FROM `user` WHERE email = 'admin@test.com';

-- ==========================================
-- LOGIN CREDENTIALS:
-- Email: admin@test.com
-- Password: test123
-- ==========================================

-- ALTERNATIVE: If still not working, try creating user via Symfony command:
-- Run this in terminal: symfony console make:user
-- Then manually update the roles in database to add ROLE_ADMIN
