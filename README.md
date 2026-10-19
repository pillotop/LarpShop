# LarpShop

LarpShop is an educational e-commerce web application built with Laravel and PHP. The project focuses on learning modern web development, application architecture, database interaction, and security-conscious design.

## Tech Stack

* **Backend:** PHP 8.4, Laravel 13
* **Database:** MySQL (development), SQLite (in-memory for tests)
* **ORM:** Laravel Eloquent
* **Frontend:** Blade, HTML, CSS, JavaScript
* **UI:** Bootstrap 5 — included via CDN (`<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">`) or npm (`import 'bootstrap/dist/css/bootstrap.min.css'`)
* **Testing:** PHPUnit / Pest
* **Development:** Docker
* **Deployment:** Render

The project intentionally avoids a CSS build step for the base UI — Bootstrap 5 is included via CDN, keeping the frontend pipeline simple and Node.js-free in accordance with the course requirements.

## Architecture

LarpShop is organized into four conceptual layers. Understanding these boundaries helps avoid crossing them incorrectly — Laravel's own conventions handle much of the wiring.

```text
┌─────────────────────────────────────────────────────────────┐
│  Presentation Layer                                         │
│  Routes → Controllers → Form Requests → Blade Views        │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  Application Layer                                          │
│  Services / Use Cases · Authorization · Transactions       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  Domain & Query Logic                                       │
│  Product Rules · Catalog Queries · Pricing · Inventory     │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  Infrastructure & Persistence                               │
│  Eloquent Models · MySQL/SQLite · Filesystem · Sessions    │
└─────────────────────────────────────────────────────────────┘
```

### Layer Responsibilities

| Layer | Purpose | Key Files |
|-------|---------|-----------|
| **Presentation** | HTTP coordination, validation, rendering | `routes/web.php`, `app/Http/Controllers/`, `app/Http/Requests/`, `resources/views/` |
| **Application** | Business orchestration, authorization, transactions | `app/Services/`, `app/Policies/`, `app/Actions/` |
| **Domain/Query** | Complex reads, business rules | `app/Queries/`, model-level logic in `app/Models/` |
| **Infrastructure** | Persistence, external services | `app/Models/`, `database/migrations/`, `database/seeders/` |

**Key principle:** Laravel already provides conventions for HTTP handling, models, validation, views, and persistence. Don't create new folders that Laravel already has opinions about. Cross-layer boundaries by following existing patterns: `routes → controllers → form requests → views` or `controllers → services → models`.

## Project Structure (Target Layout)

When the codebase matures, this is the directory organization to strive for:

```
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
├── Models/
│   ├── User.php
│   ├── Vendor.php
│   ├── Product.php
│   ├── ProductImage.php
│   ├── Category.php
│   ├── Order.php
│   ├── OrderDetail.php
│   └── Review.php
├── Policies/
│   ├── ProductPolicy.php
│   ├── OrderPolicy.php
│   └── ReviewPolicy.php
├── Actions/
│   ├── Products/
│   ├── Orders/
│   └── Reviews/
├── Queries/
│   └── Products/
├── Services/
│   ├── Products/
│   └── Payments/
└── View/
    └── Components/
database/
├── factories/
├── migrations/
├── seeders/
└── seed-data/
resources/
├── css/
├── js/
└── views/
    ├── layouts/
    ├── components/
    └── pages/
routes/
├── web.php
└── console.php
tests/
├── Feature/
└── Unit/
tools/
└── catalog-importer/
```

## Frontend

The frontend uses Blade layouts and Bootstrap 5 components for responsive structure and UI elements.

Bootstrap 5 is included via CDN for the base framework, keeping the build pipeline Node.js-free:

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
```

Bootstrap provides the base responsive layout and UI components — typography, forms, buttons, navigation, modals, grids, and more. Custom project-specific styling may be added as a separate stylesheet if needed.

JavaScript is used for interactive components such as:

* Dropdown menus
* Shared dropdown portals
* Carousels
* Client-side UI state
* Interactive SVG graphics

The application uses semantic HTML elements such as `main`, `aside`, `header`, and `section` where appropriate.

Many Bootstrap components require only markup and data attributes — no JavaScript initialization is needed. For example, a collapse menu or modal can be triggered purely through `data-bs-toggle` attributes.

For components that do require JavaScript, Bootstrap 5's bundled JS (or vanilla JS) can be used without jQuery dependency.

## Database

LarpShop uses **MySQL** for development (`DB_CONNECTION=mysql` in `.env`) and **in-memory SQLite** for tests (configured in `phpunit.xml`).

Eloquent provides the application's ORM layer. All models use custom primary keys:

| Model | PK Column |
|-------|-----------|
| `Product` | `product_id` |
| `Category` | `category_id` |
| `Vendor` | `vendor_id` |
| `ProductImage` | `product_image_id` |
| `Order` | `order_id` |
| `OrderDetail` | `order_detail_id` |
| `User` | `user_id` |

When querying, always verify the PK column name. In Eloquent relationships, explicit foreign key specification may be needed where Laravel's conventions don't match the schema.

## Running Locally

### Prerequisites

- PHP 8.4+
- Composer
- MySQL 8.0+ (or MariaDB 10.6+)
- Node.js 18+ (for Vite asset compilation, optional)

### Setup

```sh
# 1. Install PHP dependencies
composer install

# 2. Configure environment (.env already exists with MySQL settings)
#    Update DB_DATABASE, DB_USERNAME, DB_PASSWORD if needed
php artisan key:generate

# 3. Run migrations
php artisan migrate --force

# 4. (Optional) Seed the database
php artisan db:seed

# 5. Compile frontend assets (optional, only for custom JS/CSS)
npm install
npm run build

# 6. Start the development server
php artisan serve --host=127.0.0.1 --port=8000
```

Visit `http://localhost:8000`

### Running Tests

```sh
# All tests
php artisan test

# Or directly with Pest
vendor/bin/pest

# With coverage
php artisan test --coverage
```

Note: Tests use in-memory SQLite (`phpunit.xml` configures `DB_DATABASE=:memory:` and `BCRYPT_ROUNDS=4`). Auth tests require `SESSION_DRIVER=database` in `.env`.

## Docker

### Development with Docker Compose

```sh
# Start MySQL + App containers
docker compose up -d

# Run migrations inside the app container
docker compose exec app php artisan migrate --force

# View logs
docker compose logs -f app
```

The `compose.yaml` starts:
- **app** — Laravel application (PHP-FPM + Nginx)
- **mysql** — MySQL 8.0 database

### Environment

Docker uses the same `.env` file. Ensure these are set:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=larpshop
DB_USERNAME=larpshop
DB_PASSWORD=your-secure-password
```

### Building the Image

```sh
docker build -t larpshop .
```

## Deployment (Render)

The project is configured for deployment on Render. See `render.yaml` for the service configuration.

Required environment variables on Render:
- `APP_KEY` — generate with `php artisan key:generate --show`
- `DB_CONNECTION=mysql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — from Render MySQL addon
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`

## Goals

The main goals of LarpShop are to:

* Practice ORM-based database development
* Build reusable server-rendered UI components
* Develop interactive frontend components with vanilla JavaScript
* Apply security-conscious development practices
* Understand the separation between server-side application logic and client-side behavior
* Produce a deployable web application

## Useful Commands

```sh
# Code formatting
vendor/bin/pint

# Linting
vendor/bin/phpcs

# Clear all caches
php artisan optimize:clear

# Rebuild autoload
composer dump-autoload -o

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```