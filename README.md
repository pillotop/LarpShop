# LarpShop

LarpShop is a multi-category, multi-vendor e-commerce application built as a university Web Programming project. It uses Laravel for the backend, server-rendered Blade views, and reusable Bootstrap and vanilla JavaScript components.

## Tech stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13; Docker uses PHP 8.4 CLI |
| Database | MySQL; Docker Compose uses MySQL 8.4 |
| Frontend | Blade, Bootstrap 5.3, Bootstrap Icons, vanilla JavaScript |
| Asset pipeline | Node.js, npm, Vite 8, Laravel Vite plugin |
| Tests | PHPUnit 12, isolated MySQL database `larpshop_testing` |
| Development services | Docker Compose, Mailpit |

Bootstrap and Bootstrap Icons are installed through npm. Vite bundles and minifies project CSS and JavaScript for production. Snap.svg and Google Fonts still use their existing CDN URLs.

## Application features

The application includes product browsing, category navigation, keyword search, filtering, sorting, product details, and vendor storefronts. Customer flows include account profiles and addresses, a shared cart, checkout, inventory reservation, fake and PayPal payment flows, order history and details, and cancellation of eligible orders.

Customers can save products to a wishlist in their profile. Customers with a paid order can review each purchased product once, using a shared review form available on product detail pages and in order history. Product reviews support incremental loading.

Vendor and admin areas provide store, product, category, and moderation tools. Delivery tracking and customer return/refund requests are still future work; payment status and order lifecycle status are separate concepts.

## Architecture and source layout

```text
Routes → Controllers → Form Requests
                    → Queries / Services → Eloquent Models
                    → Blade views and components
```

Controllers coordinate HTTP requests. Form Requests validate input, queries centralize database reads, and services handle workflows and transactions. Catalog page controllers live in `app/View`; action controllers and other HTTP controllers live in `app/Http/Controllers`.

```text
app/
├── Contracts/           # Provider interfaces
├── Data/                # Data transfer objects
├── Enums/               # Order, payment, and other state values
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Queries/
├── Services/
└── View/                # Catalog page controllers and view composers
resources/
├── css/                 # Source styles, including component styles
├── js/                  # Shared initialization and component modules
└── views/               # Blade layouts, pages, partials, and components
routes/                  # Web routes split by feature; scheduled commands
public/
├── build/               # Generated Vite output (not committed)
└── images/              # Static images and SVG assets
database/                # Migrations, factories, and seeders
tests/                   # Feature and unit tests
docs/                    # Architecture and workflow notes
```

Models commonly use custom primary keys, such as `user_id`, `product_id`, `vendor_id`, `order_id`, and `review_id`. Verify the model's key before writing relationships, queries, or route bindings.

## Running locally

### Prerequisites

- PHP 8.3+ with the extensions required by Composer, including `pdo_mysql`.
- Composer 2.
- MySQL (the Docker environment uses MySQL 8.4).
- Node.js supported by Vite 8; Node 22.13.1 has been verified with this project.
- npm.

### Initial setup

From the project root:

```bash
# Create local configuration if it does not already exist.
test -f .env || cp .env.example .env
composer install
php artisan key:generate
```

Configure a development database and its credentials in `.env`:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=larpshop
DB_USERNAME=larpshop
DB_PASSWORD=your-local-password
```

Create the database and grant the configured account access before running migrations. Keep local credentials in `.env`.

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Seeding is optional and supplies sample data. Uploaded product images use Laravel's public storage disk and the `public/storage` symlink.

Open [http://localhost:8000](http://localhost:8000).

### Frontend development

Run Vite in another terminal while Laravel is running:

```bash
npm run dev
```

Blade's `@vite` directive loads assets from the development server while it is running. For a production-style local run, stop the Vite server and build the assets:

```bash
npm run build
```

The build generates minified files with content hashes, icon fonts, and `public/build/manifest.json`. Rebuild whenever frontend source files change. Production serves these generated files without a running Node.js server.

Add styles under `resources/css` and scripts under `resources/js`. Load page-specific assets with grouped `@vite([...])` directives. The Vite configuration discovers CSS and JavaScript entries recursively. Shared layouts load the common entries, and page modules declare their initialization dependencies through imports.

See [Frontend assets](docs/FRONTEND_ASSETS.md) for loading conventions and migration details.

### Background processing

When exercising queued notifications and scheduled order/payment maintenance, run these in separate terminals:

```bash
php artisan queue:work
php artisan schedule:work
```

The scheduler expires unpaid orders every minute and reconciles pending payments every five minutes. Payment initiation and completion use shared cache locks; use a shared cache store such as the configured database store when multiple processes serve requests.

## Testing

Tests use **MySQL**, with `DB_CONNECTION=mysql` and `DB_DATABASE=larpshop_testing` forced by `phpunit.xml`. Host, port, username, and password come from the environment. Tests use array-backed sessions and cache, synchronous queues, and the array mailer unless a particular test overrides them.

Before the first database test run:

1. Create a separate database named `larpshop_testing`.
2. Grant the account used by the tests permission to create and modify tables in that database.
3. Build the frontend with `npm ci` and `npm run build` so rendered Blade views can resolve Vite entries.

For example, create the database using a MySQL administrator account:

```sql
CREATE DATABASE IF NOT EXISTS larpshop_testing
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Grant privileges to the actual MySQL account and host configured for your environment. Creating the database alone does not grant the application user access.

```bash
php artisan config:clear
php artisan test
php artisan test --filter=ProductReviewTest
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

Database tests can recreate tables in `larpshop_testing`; keep it separate from development and production databases. A passing focused suite does not imply that every test in the repository has been updated for every ongoing feature change.

## Docker Compose

The current Compose environment provides:

| Service | Purpose | Access |
| --- | --- | --- |
| `app` | PHP 8.4 CLI running Laravel's development server | `http://localhost:8000` |
| `db` | MySQL 8.4 with persistent `mysql_data` volume | Internal service hostname `db` |
| `mailpit` | Local email capture | `http://localhost:8025` |

Prepare `.env` and `APP_KEY` as described above. Set both `DB_PASSWORD` and `DB_ROOT_PASSWORD` in your local `.env`; Compose requires them. The application container receives `DB_HOST=db` and the development database settings from `compose.yaml`.

The current Dockerfile does not install Node.js or build frontend assets. Build them on the host **before building the image**, so `public/build` is included:

```bash
npm ci
npm run build
docker compose up -d --build
```

Once MySQL is ready:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan storage:link
docker compose logs -f app
```

The Compose file does not publish MySQL to the host, so host-side PHP cannot connect to this database through `127.0.0.1:3306` without additional port configuration. Inside Compose, use `db:3306`.

To run tests inside the application container, create and grant access to `larpshop_testing` in the container's MySQL service first, then run:

```bash
docker compose exec -T app php artisan test
```

The image copies source files and built assets at build time; it does not mount the working tree. Rebuild the image after source or asset changes. Preserve the development database volume; avoid destructive reset commands against it.

## Deployment considerations

Deploy the Laravel application with `public/build` generated by `npm ci && npm run build`. Configure the application key, database, mail, cache, queue, and payment provider settings through the deployment environment. Point the web server's document root at `public` and provide persistent writable storage for uploads.

Run a queue worker for queued jobs and Laravel's scheduler for periodic maintenance. The included Dockerfile uses Laravel's development server; production hosting needs an appropriate serving configuration. There is currently no `render.yaml` deployment configuration in the repository.

## Useful commands

```bash
# Format PHP code.
vendor/bin/pint

# Build frontend assets.
npm run build

# Clear Laravel caches.
php artisan optimize:clear

# Run scheduled maintenance manually.
php artisan orders:expire
php artisan payments:reconcile
```

## Project documentation

- [Frontend assets](docs/FRONTEND_ASSETS.md) — Vite, npm, source locations, and build conventions.
- [Order status model](docs/ORDER_STATUS_MODEL.md) — Order lifecycle and payment status responsibilities.
- [Account and vendor refactor](docs/ACCOUNT_VENDOR_REFACTOR.md) — Account/store relationship changes.
- [Project context](docs/PROJECT_CONTEXT.md) — Architecture background and catalog feature mappings.
- [Authorized security review workflow](docs/PENTEST_WORKFLOW.md) — Local testing scope and review conventions.

Some historical notes still describe the former SQLite test setup or CDN frontend. The current `phpunit.xml`, `package.json`, and `vite.config.js` define the active configuration.
