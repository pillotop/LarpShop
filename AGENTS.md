# LarpShop — Laravel 13 + MySQL + Pest

## Quickstart
```sh
composer install
# .env already configured for MySQL
php artisan key:generate
php artisan migrate --force
php artisan serve --host=127.0.0.1 --port=8000
```

## Database
- **Default:** MySQL (`DB_CONNECTION=mysql` in `.env`)
- **phpunit.xml** uses in-memory SQLite (`DB_DATABASE=:memory:`) with `BCRYPT_ROUNDS=4` for fast tests
- **`.env`** has `BCRYPT_ROUNDS=12`; phpunit.xml overrides to `4` for tests

## Custom Primary Keys
All models use non-default primary keys. Verify the PK column when querying:

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
- `SESSION_DRIVER` must be `database` in `.env` for auth tests (phpunit.xml uses `array`)
- Factories in `database/factories/`, seeders in `database/seeders/`
- Feature tests live in `tests/Feature/`, Unit in `tests/Unit/`
- Tests use `Tests\TestCase` base class (extends Laravel's `TestCase`)

## Formatting & Linting
- **Pint** is the PHP code formatter (`vendor/bin/pint`)
- **PHPCS** uses PSR-12 rules (`vendor/bin/phpcs`)
- Run `pint` before commit; `phpcs` to check

## Frontend

- **Bootstrap 5** — included via CDN (`<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">`) or npm (`import 'bootstrap/dist/css/bootstrap.min.css'`)
- **Vite** handles assets: `npm run build` / `npm run dev` (for custom JS, not base CSS)
- Resources: `resources/css/app.css` (custom project styles), `resources/js/app.js`
- The base UI CSS is handled by Bootstrap CDN; no `@vite` directive is needed for it. Custom JS components can use the `@vite(...)` directive in Blade templates when a build step is present.

## Architecture (conceptual layers)

LarpShop is organized into four conceptual layers. Understanding these boundaries helps avoid crossing them incorrectly — Laravel's own conventions handle much of the wiring, so you don't need to create the underlying folder structure yourself.

### Presentation layer
- **Routes** — defined in `routes/web.php` and included sub-files (`routes/products.php`, `routes/auth.php`)
- **Controllers** — `app/Http/Controllers/`, extend the base `Controller`. Most are thin, delegating to services or using implicit route model binding.
- **Form Requests** — validation lives in `app/Http/Requests/` (e.g., product creation, image upload). They run before controller logic.
- **Blade views** — `resources/views/`. Layouts, components, and feature pages. All UI CSS is Bootstrap 5 via CDN; no build step required for base styles.
- **View components** — reusable Blade components in `resources/views/components/`.

### Application layer
- **Services/use-case logic** — business orchestration lives in `app/Services/` or implicit controller methods. This project keeps service logic minimal; heavy use-case logic would go here.
- **Authorization** — `App\Models\User` has a `vendor` relationship (`vendor_id` PK). Policy/gate decisions based on `user_id` ownership check.
- **Transaction coordination** — `ProductImageController::store` saves an image within the same request; no explicit `DB::transaction` blocks are used in the current codebase.

### Domain and query logic
- **Product rules** — custom PK is `product_id` (not `id`). Category and vendor relationships use BelongsTo with matching foreign IDs.
- **Catalog queries** — products are fetched with eager-loaded relations: `Product::with('category', 'images')`.
- **Pricing** — `Order::total_amount` is a `decimal(10,2)`; no separate pricing engine exists.
- **Inventory** — stock-level tracking is not implemented in the current schema.

### Infrastructure and persistence
- **Eloquent models** — `app/Models/` (all 7 models use custom PKs: `product_id`, `category_id`, `vendor_id`, `product_image_id`, `order_id`, `order_detail_id`, `user_id`).
- **Database** — MySQL in development (`DB_CONNECTION=mysql` in `.env`), in-memory SQLite for tests (`phpunit.xml`). Migrations live in `database/migrations/`.
- **Filesystem** — `storage/app/` for uploaded files; `ProductImage::store('products', 'public')` uses the public disk.
- **Sessions** — `SESSION_DRIVER=database` in `.env` for auth tests; phpunit.xml overrides to `array`.
- **Mail** — mailer is `log` array (no SMTP sending in the current codebase).
- **External services** — Livewire (`actions/`, `forms/`) and third-party APIs would go here; none are configured yet.

**Key takeaway:** Laravel already provides conventions for HTTP handling, models, validation, views, and persistence. You generally shouldn't create new folders that Laravel already has opinions about (e.g., don't invent a `use_cases/` directory if the project keeps logic in controllers/services). Cross-layer boundaries by following the existing patterns: routes → controllers → form requests → views, or controllers → services → models.

## Proposed repository structure (target layout)

When the codebase matures, this is the directory organization to strive for. It keeps Laravel's standard structure recognizable while introducing feature-oriented boundaries inside it.

### Key directories and their purpose

- **`app/Http/Controllers/`** — HTTP coordination. Controllers receive requests, invoke operations, and return responses. Should not contain complex catalog filtering, order calculations, or database orchestration.

- **`app/Http/Requests/`** — Input validation. Form Requests validate incoming data before it reaches controllers.

- **`app/Policies/`** — Authorization. Policies decide whether a user can perform an operation on a resource. Authentication alone does not establish authorization.

- **`app/Models/`** — Persistence and relationships. Eloquent models represent database entities and define relationships. Can contain appropriate model-level behavior, but shouldn't become containers for every application workflow.

- **`app/Actions/`** — Operations that change application state. Example: `PlaceOrder` makes checkout explicit (validate cart, check stock, calculate totals, create order records, update inventory). Create only when a workflow is complex enough to warrant its own class.

- **`app/Queries/`** — Complex reads. Owns eager loading, filters, sorting, and aggregate calculations without making controllers or Blade views responsible for database query construction.

- **`app/Services/`** — Reusable coordination or integrations. Keep a service when it represents a meaningful responsibility. Don't create one simply to forward every call from a controller to an Eloquent model.

- **`resources/views/`** — Presentation. Page templates compose reusable Blade components. Components render UI; they shouldn't independently authorize purchases or decide ownership.

- **`database/factories/`**, **`database/seeders/`**, **`database/seed-data/`** — Test data and fixtures.

- **`routes/web.php`** — Web routes; includes sub-files for products, auth, etc.

- **`tools/`** — Project-specific tools (e.g., `catalog-importer/`).

### Custom primary keys reminder

Your current models use non-default primary keys (`product_id`, `category_id`, `vendor_id`, `product_image_id`, `order_id`, `order_detail_id`, `user_id`). When querying, always verify the PK column name. In Eloquent relationships, explicit foreign key specification may be needed where Laravel's conventions don't match your schema.

**Key takeaway:** Start with Laravel's standard structure. Introduce `Actions`, `Queries`, `Policies`, and `Services` only when the complexity justifies them. Don't create empty classes just because they appear in a target layout — add them when you have the actual workflow or responsibility to justify them.