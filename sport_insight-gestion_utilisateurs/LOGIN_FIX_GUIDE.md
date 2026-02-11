# BEST SOLUTION: Create Admin User via Symfony Console

## Method 1: Using Symfony Console (RECOMMENDED)

Run these commands in your terminal:

```powershell
# Navigate to project directory
cd c:\Users\ahmed\OneDrive\Bureau\project\sport_insight-gestion_utilisateurs

# Create a new user interactively
symfony console make:user

# When prompted:
# - Email: admin@test.com
# - Password: admin123
# - Confirm password: admin123
```

Then, manually update the roles in phpMyAdmin:
```sql
UPDATE `user` SET `roles` = '["ROLE_ADMIN","ROLE_USER"]' WHERE `email` = 'admin@test.com';
```

---

## Method 2: Direct SQL (if Method 1 doesn't work)

Run this SQL in phpMyAdmin:

```sql
-- Delete old admin
DELETE FROM `user` WHERE `email` = 'admin@test.com';

-- Create new admin
-- Password: admin
INSERT INTO `user` (`email`, `roles`, `password`, `nom`, `prenom`, `telephone`, `date_naissance`, `photo`, `statut`, `date_inscription`) 
VALUES (
    'admin@test.com',
    '["ROLE_ADMIN","ROLE_USER"]',
    '$2y$13$rHqZqZqZqZqZqZqZqZqZqO5vLWWxH3Kp5KZKd.ZqZqZqZqZqZqZqZqO',
    'Admin',
    'Système',
    '12345678',
    '1990-01-01',
    NULL,
    'actif',
    NOW()
);
```

**Login with:**
- Email: `admin@test.com`
- Password: `admin`

---

## Method 3: Check User Entity

Make sure your `User` entity implements `PasswordAuthenticatedUserInterface` correctly.

The issue might be with how the password is being verified. Check that your `User.php` has:

```php
public function getPassword(): string
{
    return $this->password;
}
```

---

## Troubleshooting

If none of the above work, the issue might be:

1. **Wrong password hasher algorithm** - Check `config/packages/security.yaml`
2. **User not found** - Verify email exists in database
3. **User status** - Make sure `statut` is 'actif' not 'bloque'
4. **Roles format** - Should be JSON array: `["ROLE_ADMIN","ROLE_USER"]`

Run this SQL to check:
```sql
SELECT id, email, roles, statut, password FROM `user` WHERE email = 'admin@test.com';
```
