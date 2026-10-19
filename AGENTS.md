# LarpShop — Laravel 13 + SQLite + Pest

## Quickstart

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan serve --host=127.0.0.1 --port=8000
```

## Database

- **Default:** SQLite (`DB_CONNECTION=sqlite` in `.env`)
- **phpunit.xml** uses in-memory SQLite (`DB_DATABASE=:memory:`)
- **.env.example** has `BCRYPT_ROUNDS=12`; **phpunit.xml** overrides to `4` for fast tests
- Test suite: `php artisan test` or `vendor/bin/pest`

## Custom Primary Keys

Multiple models use non-default primary keys — verify the key column when querying:

| Model          | PK column   |
|----------------|-------------|
| `Product`      | `product_id` |
| `Category`     | `category_id` |
| `Vendor`       | `vendor_id` |
| `ProductImage` | `product_image_id` |
| `Order`        | `order_id` |
| `OrderDetail`  | `order_detail_id` |
| `User`         | `user_id` |

Example: `Product::find(1)` works, but always check the PK column name.

## Route Model Binding

`Route::get('/products/{product}/{slug?}')` binds `{product}` by `product_id`. If `{slug}` doesn't match `$product->slug`, a redirect occurs. Don't assume `{slug}` is the primary key.

## Testing Quirks

- PHPUnit configures `DB_CONNECTION=sqlite` with `:memory:` and `BCRYPT_ROUNDS=4`
- Factories are in `database/factories/`
- Seeders are in `database/seeders/`
- Feature tests live in `tests/Feature/`, Unit in `tests/Unit/`
- Tests use the `Tests\TestCase` base class (extends Laravel's `TestCase`)
- Auth tests use database session driver; ensure `.env` has `SESSION_DRIVER=database` for auth tests

## Formatting & Linting

- **Pint** is the PHP code formatter (`vendor/bin/pint`)
- **PHPCS** uses PSR-12 rules (`vendor/bin/phpcs`)
- Run: `pint` / `phpcs`

## Frontend

- **TailwindCSS v3.1.0** — config in `tailwind.config.js`
- **Vite** handles assets: `npm run build` / `npm run dev`
- Resources: `resources/css/app.css`, `resources/js/app.js`
- Blade `@vite(...)` directive in `welcome.blade.php` when `build/manifest.json` exists

## Docker (optional)

```sh
docker compose up -d    # starts app + MySQL
```

- `compose.yaml` defaults to MySQL (`DB_CONNECTION=mysql`), but `.env.example` uses SQLite
- Adjust `.env` if switching between Docker and local development

## Directory Ownership

- `app/Models/` — Eloquent models (all have custom PKs)
- `app/Http/Controllers/` — Controllers (`ProductImageController` + base `Controller`)
- `routes/web.php` — Web routes; note inline `ProductImageController::store` route
- `resources/views/` — Blade templates (welcome, homepage, product, category, login, supports, vendor forms)
- `database/` — migrations, seeders, factories
- `public/` — compiled assets; `css/`, `js/`, `images/`