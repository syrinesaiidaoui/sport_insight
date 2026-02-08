# Sport Insight

A Symfony-based sports management application with equipment tracking, order management, and admin functionality.

## Setup

### Requirements

- PHP 8.2+
- Composer
- SQLite or MySQL

### Installation

1. Clone the repository
```bash
git clone <repository-url>
cd sport_insight-main
```

2. Install dependencies
```bash
composer install
```

3. Configure environment
```bash
cp .env .env.local
# Edit .env.local and configure DATABASE_URL if needed
```

4. Create and migrate database
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Create admin user (optional)
```bash
php bin/console make:user
```

## Running the Application

### Development Server

Start the local development server:
```bash
symfony server:start
# or
php -S localhost:8000 -t public
```

Access the application at `http://localhost:8000` (or the URL shown by symfony CLI)

### Docker Compose

If using Docker:
```bash
docker-compose up -d
```

## Testing

### Run All Tests

```bash
php bin/phpunit
# or
./vendor/bin/phpunit
```

### Run Specific Test File

```bash
php bin/phpunit tests/ProductEntityTest.php
```

### Run Tests with Coverage

```bash
php bin/phpunit --coverage-html=var/coverage
```

## Project Structure

- **src/Controller/** - Application controllers
  - **BackOffice/** - Admin controllers (require ROLE_ADMIN)
  - **FrontOffice/** - User-facing controllers
- **src/Entity/** - Doctrine ORM entities
- **templates/** - Twig templates
  - **back_office/** - Admin interface templates
  - **front_office/** - User interface templates
- **tests/** - PHPUnit tests
- **migrations/** - Database migrations

## Main Features

### BackOffice (Admin)
- Equipment management (CRUD operations)
- Order management and tracking
- Protected by ROLE_ADMIN

### FrontOffice (User)
- Browse equipment catalog
- Purchase equipment
- View order history at `/equipement/orders`

## Routes

### Admin Routes (require ROLE_ADMIN)
- `/admin/equipement/` - Equipment catalog
- `/admin/equipement/new` - Create new equipment
- `/admin/equipement/{id}/edit` - Edit equipment
- `/admin/equipement/{id}/delete` - Delete equipment
- `/admin/orders/` - View all orders

### User Routes
- `/equipement/` - Browse equipment
- `/equipement/{id}/buy` - Purchase equipment
- `/equipement/orders` - View user's orders

## Development Notes

- All admin routes are protected with `ROLE_ADMIN` permission
- Database uses Doctrine ORM with automatic migrations
- Equipment stock is automatically decreased on purchase
- Orders track user, product, quantity, date, and status

## License

Proprietary - Sport Insight
