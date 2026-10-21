# LarpShop — Verified Project Context

> **Purpose:** This document records the verified technical state, architecture, database schema, use-case implementation status, and development environment of the **LarpShop** repository (`https://github.com/pillotop/LarpShop`) so any engineer or AI coding agent can continue work accurately without losing context.
>
> **Last Verified Against Repository:** Branch `feature/uc04-product-search` (HEAD commit `852089c` + uncommitted working-tree enhancements for UC-04).

---

## 1. Business Goals & Confirmed Use Cases (UC-01 to UC-08)

**LarpShop** is a multi-category, multi-vendor e-commerce web application built for a university Web Programming course final project. Its primary business objective is to let guests and customers discover, browse, classify, search, filter/sort, and inspect products across categories and vendor stores.

### Team Ownership Summary (Verified from Team Assignment Specification)
- **Việt (`V`):** Lead backend architect, database design, authentication/authorization (`UC-09`, `UC-10`, `UC-14`, `UC-16`), cart & checkout (`UC-17`..`UC-21`, `UC-24`), vendor inventory/order fulfillment (`UC-33`..`UC-35`), and category/user admin (`UC-39`, `UC-41`, `UC-44`). Also reviews PRs for `UC-04` and database/permission changes.
- **Trí (`T`):** Product discovery frontend/full-stack (`UC-01`, `UC-02`, `UC-03`, `UC-04`, `UC-05`, `UC-06`, `UC-08`), customer post-purchase & account experience (`UC-13`, `UC-22`, `UC-23`, `UC-25`, `UC-27`, `UC-28`), and authorized web application security testing (Pentest, evidence collection, and retesting after remediation).
- **Khang (`K`):** Vendor storefront (`UC-07`), account recovery/profile/vendor onboarding (`UC-11`, `UC-12`, `UC-15`), vendor product & store management (`UC-29`..`UC-32`, `UC-36`, `UC-37`), and admin moderation/dashboard (`UC-38`, `UC-40`, `UC-42`, `UC-43`, `UC-45`).

### Confirmed Catalog Discovery Scope (UC-01 through UC-08)

| ID | Use Case | Priority | Owner | Actors | Business Goal |
| :--- | :--- | :---: | :---: | :--- | :--- |
| **UC-01** | Homepage and Featured Products | `P0` | Trí (`T`) | Guest, Customer | Introduce the store, showcase category banners, and render database-backed products using reusable cards. |
| **UC-02** | Browse Product Categories | `P0` | Trí (`T`) | Guest, Customer | Navigate to a category by slug, view subcategories and belonging products, and handle empty/invalid categories gracefully. |
| **UC-03** | Browse Products by Classification | `P0` | Trí (`T`) | Guest, Customer | Browse products grouped by the hierarchical category/classification model (`parent_id` / subcategories) and UI classification tabs. |
| **UC-04** | Product Search by Name or Keyword | `P0` | Trí (`T`) *(Việt reviews)* | Guest, Customer | Search catalog via `GET /products?q=keyword` across product `name` and `description` safely in SQL with clear empty states. |
| **UC-05** | Filter and Sort Products | `P1` | Trí (`T`) | Guest, Customer | Narrow catalog results by valid criteria (e.g., category, price range, stock availability) and order by allowlisted sort options. |
| **UC-06** | Product Detail Page | `P0` | Trí (`T`) | Guest, Customer | Inspect a single product's images, name, description, price, stock status, rating summary, category breadcrumb, and vendor info. |
| **UC-07** | Vendor Store Pages | `P1` | Khang (`K`) | Guest, Customer | View an individual vendor store profile and browse products belonging to that vendor. |
| **UC-08** | Related Products | `P2` | Trí (`T`) | Guest, Customer | Suggest related products on the product detail page (excluding the current product, avoiding duplicates, handling empty sets). |

---

## 2. Verified Technology Stack & Dependency Versions

All versions below are verified directly from `composer.json`, `Dockerfile`, `compose.yaml`, `phpunit.xml`, `package.json`, and `resources/views/layouts/app.blade.php`:

| Layer | Package / Tool | Verified Version / Configuration | Source File |
| :--- | :--- | :--- | :--- |
| **PHP Runtime** | PHP | `^8.3` required (`php:8.4-cli` in Docker) | `composer.json`, `Dockerfile` |
| **Framework** | `laravel/framework` | `^13.17` | `composer.json` |
| **Breadcrumbs** | `diglactic/laravel-breadcrumbs` | `^10.1` | `composer.json`, `routes/web.php`, `routes/products.php` |
| **Optional UI Packages** | `livewire/livewire`, `livewire/volt` | `^3.6.4`, `^1.7.0` (installed; auth routes commented out in `routes/auth.php`) | `composer.json`, `routes/auth.php` |
| **Base UI Framework** | Bootstrap 5 + Bootstrap Icons | `5.3.8` (CDN) + `1.11.3` (CDN) | `resources/views/layouts/app.blade.php` |
| **SVG / UI Scripting** | Snap.svg + Vanilla JS | `0.5.1` (CDN) + custom scripts in `public/js/components/` | `resources/views/layouts/app.blade.php`, `resources/views/homepage.blade.php` |
| **Dev Database** | MySQL | `8.4` (`mysql:8.4` image, `db` service in Docker) | `compose.yaml` |
| **Test Database** | SQLite | In-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) | `phpunit.xml` |
| **Test Runner** | PHPUnit | `^12.5.12` | `composer.json`, `phpunit.xml` |
| **Code Formatter** | Laravel Pint | `^12.7` (`^1.27` in `require-dev`) | `composer.json` |
| **Frontend Build (Optional)** | Vite + Tailwind devDeps | `vite ^8.0.0`, `laravel-vite-plugin ^3.1` (`@vite` commented out in `layouts/app.blade.php`) | `package.json`, `resources/views/layouts/app.blade.php` |

---

## 3. Existing Architecture & Request Flow

### Request Flow
```text
HTTP Request
  │
  ▼
routes/web.php (includes routes/products.php and routes/auth.php)
  │
  ▼
Controller
  ├─ Catalog page controllers in app/View/ (namespace App\View)
  └─ Action/JSON/Auth controllers in app/Http/Controllers/ (namespace App\Http\Controllers)
  │
  ▼
Request Validation ($request->validate([...]))
  │
  ▼
Service / Query Layer
  ├─ App\Services\Products\ProductRetrievalService
  ├─ App\Queries\Products\ProductCatalogQuery
  ├─ App\Services\Cart\CartRetrievalService
  └─ App\Queries\Cart\CartQuery
  │
  ▼
Eloquent Models (app/Models/*) ──► MySQL 8.4 (dev) / SQLite :memory: (PHPUnit)
  │
  ▼
View Composer (App\View\Composers\ProductCapabilityComposer injects $cart)
  │
  ▼
Blade Page Template (resources/views/**) + Reusable Blade Components (resources/views/components/**)
```

### Key Architectural Observations (Verified Facts)
1. **Split Controller Namespaces:**
   - Catalog page controllers (`HomeController`, `ProductController`, `ProductSearchController`, `CategoryController`) are placed in `app/View/` under namespace `App\View` and extend `App\Http\Controllers\Controller`.
   - Stateful/JSON/Auth controllers (`CartController`, `ProductImageController`, `Auth\LoginController`, `Auth\RegistrationController`, `Auth\PasswordResetController`, `Auth\VerifyEmailController`) are in `app/Http/Controllers/`.
2. **Service vs. Query Usage:**
   - `HomeController`, `ProductController`, and `CategoryController` call `ProductRetrievalService`, which forwards calls to `ProductCatalogQuery`.
   - `ProductSearchController` injects `ProductCatalogQuery` directly and calls `searchProducts($keyword)`.
3. **View Composer Registration:**
   - `AppServiceProvider::boot()` registers `ProductCapabilityComposer` for 4 views: `homepage`, `products.pages.index`, `products.pages.show`, and `categories.pages.show`.
   - Any view rendering `<x-nav-bar :cart="$cart" ... />` requires `$cart` (an array with keys `'total_count'` and `'items'`).

---

## 4. Route, Controller, Query, Model & Blade Component Mappings

### 4.1 Verified Route Inventory

| Method | URI | Route Name | Handler | Notes / Status |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | `home` | `App\View\HomeController@index` | Renders `homepage.blade.php` with DB categories and up to 12 in-stock products. |
| `GET` | `/products` | `products.index` | `App\View\ProductSearchController@index` | Validates `q` (`nullable\|string\|max:100`), runs `ProductCatalogQuery::searchProducts()`, renders `products.pages.index`. |
| `GET` | `/categoriess/{slug}` | `categories.show` *(overwritten)* | `App\View\CategoryController@show` | **Typo in URI (`/categoriess/`)**. Uses real DB category & products, renders `categories.pages.show`. |
| `GET` | `/categories/{slug}` | `categories.show` | Closure in `routes/web.php` | **Overrides `categories.show` route name** and renders static mockup `categories.pages.category` with hardcoded Lorem Ipsum items. |
| `GET` | `/products/{product}/{slug?}` | `products.show` | `App\View\ProductController@show` | Defined in `routes/products.php`. Binds `{product}` by `product_id`. Redirects if `$slug !== $product->slug`. |
| `GET` | `/products/cart` | `cart.index` | `App\Http\Controllers\CartController@index` | Returns JSON cart contents for current user or session. |
| `POST` | `/products/cart` | `cart.store` | `App\Http\Controllers\CartController@store` | Validates `product_id` and `quantity`, checks stock, updates/creates `CartItem`, returns JSON. |
| `POST` | `/products/{product}/images` | `products.images.store` | `App\Http\Controllers\ProductImageController@store` | Uploads image (`max:5120`) to `public` disk under `products/`. **Note:** Currently lacks auth/policy middleware on the route. |
| `GET` | `/vendor/products/create` | `vendor.product.create` | Closure in `routes/web.php` | Renders `vendor.pages.create_product`. |
| `POST` | `/vendor/products` | `vendor.product.store` | Closure in `routes/web.php` | Prototype closure returning `vendor.success` view (view file not yet present). |
| `GET` | `/supports` | *(unnamed)* | Closure in `routes/web.php` | Renders `misc.pages.supports`. |
| `POST` | `/auth/check-email` | `auth.check-email` | Closure in `routes/auth.php` | Checks if email exists (`throttle:10,1`). |
| `GET` | `/login` | `login` | `Auth\LoginController@create` | Guest middleware. |
| `POST` | `/login` | `login.store` | `Auth\LoginController@store` | Guest middleware. |
| `POST` | `/register` | `register.store` | `Auth\RegistrationController@store` | Guest middleware. |
| `POST` | `/logout` | `logout` | `Auth\LoginController@destroy` | Auth middleware. |
| `GET/POST` | `/password-reset` | `password-reset(.store)` | `Auth\PasswordResetController` | Auth middleware. |

### 4.2 Query & Service Methods
- **`App\Queries\Products\ProductCatalogQuery`:**
  - `getAllCategoryIds(int $categoryId): array` (private) — recursively collects `$categoryId` and all descendant category IDs via `Category::with('children')`.
  - `getProducts(?int $categoryId = null, int $limit = 12, bool $inStockOnly = true): Collection` — eager-loads `images`, `vendor`, `reviews`; filters by category tree and `stock > 0`; adds `average_rating` and `total_reviews`; orders by `created_at DESC, product_id DESC`.
  - `getProductDetails(int $productId): ?Product` — eager-loads `images`, `vendor`, `reviews` with rating aggregates for a single `product_id`.
  - `getSimilarProducts(int $productId): ?Collection` — currently queries all products `whereKeyNot($productId)` without filtering by `category_id` or applying a `limit`.
  - `searchProducts(?string $keyword = null): Collection` — eager-loads `category`, `images` with rating aggregates; when `$keyword` is non-empty, applies grouped `where('name', 'like', "%$keyword%")->orWhere('description', 'like', "%$keyword%")`; orders by `created_at DESC, product_id DESC`; limits to `100`.

### 4.3 Reusable Blade Components (`resources/views/components/`)
- `<x-nav-bar>` (`nav-bar.blade.php`) — Top navigation bar with logo, category dropdown (`route('categories.show', $category->slug)`), GET search form (`action="{{ route('products.index') }}"`, `name="q"`, `value="{{ request()->query('q', '') }}"`, `maxlength="100"`), cart offcanvas trigger, and guest login link.
- `<x-product-card-a>` (`product-card-a.blade.php`) — Carousel product card used on `homepage.blade.php`. Links to `products.show`. **Known quirk:** calls `$firstImage->path` without null-safe operator (`?->`), which can fail if a product has no images.
- `<x-product-card-b>` (`product-card-b.blade.php`) — Primary catalog grid card used across `homepage`, `products/pages/index`, `products/pages/show` (similar products), and `categories/pages/show`. Safely resolves `$firstImage?->path` (supporting external URLs, `storage/...`, and placeholder fallback `images/product-placeholder.jpg`), shows `Add to cart` when `stock > 0` or `Out of Stock` badge when `stock <= 0`, and renders `<x-rating-container>`.
- `<x-category-banner>` (`category-banner.blade.php`) — Visual category promo card on the homepage.
- `<x-rating-container>` (`rating-container.blade.php`) — Displays star rating and review count.
- `<x-grid>`, `<x-two-arrow-scroller>`, `<x-rounded-section-radius>`, `<x-side-panel>`, `<x-modal>`, `<x-company-footer>`.

---

## 5. Database Entities & Verified Relationships

Verified from `database/migrations/` and `app/Models/`:

| Model | Table | Primary Key | Verified Columns | Defined Eloquent Relationships in Model |
| :--- | :--- | :--- | :--- | :--- |
| `User` | `users` | `user_id` | `user_id`, `username` (unique, 50), `email` (unique, 100), `password`, `phone_number` (nullable, 15), `role` (default `'customer'`, 20), timestamps | None defined yet on `User` model (missing `vendor()`, `orders()`, `reviews()`). |
| `Vendor` | `vendors` | `vendor_id` | `vendor_id`, `user_id` (FK `users.user_id`, unique, restrictOnDelete), `store_name` (100), `description` (nullable), `status` (default `'pending'`, 20), timestamps | None defined yet on `Vendor` model (missing `user()`, `products()`). |
| `Category` | `categories` | `category_id` | `category_id`, `parent_id` (nullable self-FK `categories.category_id`), `name` (100), `slug` (100), `description` (nullable), timestamps | `parent()` (`BelongsTo`), `children()` (`HasMany`). Missing `products()` (`HasMany`). |
| `Product` | `products` | `product_id` | `product_id`, `category_id` (FK, restrictOnDelete), `vendor_id` (FK, restrictOnDelete), `name` (150), `slug`, `description` (nullable), `price` (`decimal(10,2)`), `stock` (`unsignedInteger`, default 0), timestamps | `category()` (`BelongsTo`), `vendor()` (`BelongsTo`), `images()` (`HasMany`), `reviews()` (`HasMany`), `cartItems()` (`MorphMany`). |
| `ProductImage` | `product_images` | `product_image_id` | `product_image_id`, `product_id` (FK, cascadeOnDelete), `path`, `alt_text` (nullable), `sort_order` (default 0), timestamps | `product()` (`BelongsTo`). |
| `ProductReview` | `product_reviews` | `review_id` | `review_id`, `product_id` (FK, restrictOnDelete), `user_id` (FK, restrictOnDelete), `rating` (`unsignedTinyInteger`, CHECK `1..5`), `comment` (nullable), timestamps | `product()` (`BelongsTo`), `user()` (`BelongsTo`). |
| `Order` | `orders` | `order_id` | `order_id`, `user_id` (FK, restrictOnDelete), `total_amount` (`decimal(10,2)`), `status` (default `'pending'`, 50), timestamps | None defined yet on `Order` model. |
| `OrderDetail` | `order_details` | `order_detail_id` | `order_detail_id`, `order_id` (FK, restrictOnDelete), `product_id` (FK, restrictOnDelete), `quantity` (`unsignedInteger`), `unit_price` (`decimal(10,2)`), timestamps | None defined yet on `OrderDetail` model. |
| `Cart` | `carts` | `cart_id` | `cart_id`, `user_id` (nullable FK, nullOnDelete), `session_id` (nullable), timestamps | `items()` (`HasMany` `CartItem`). |
| `CartItem` | `cart_items` | `cart_item_id` | `cart_item_id`, `cart_id` (FK, cascadeOnDelete), `itemable_type`, `itemable_id` (`morphs`), `quantity` (default 1), timestamps | `cart()` (`BelongsTo`), `itemable()` (`MorphTo`). |

---

## 6. Docker Development Environment & Test Configuration

### Docker Compose (`compose.yaml` & `Dockerfile`)
- **`app` service:**
  - Built from `Dockerfile` (`php:8.4-cli` with `zip`, `pdo_mysql`, `pdo_sqlite`, and Composer 2).
  - Exposes port `8000:8000` (`php artisan serve --host=0.0.0.0 --port=8000`).
  - Reads `.env` at runtime via `env_file` and overrides `DB_CONNECTION=mysql`, `DB_HOST=db`, `DB_PORT=3306`, `DB_DATABASE=larpshop`, `DB_USERNAME=larpshop`.
  - `Dockerfile` creates an empty `.env` placeholder (`RUN touch .env`) because `.env` is excluded by `.dockerignore`, preventing `phpdotenv` stream warnings during container test runs without baking secrets into the image.
  - **Verified gotcha:** when a `.env` file exists, `php artisan serve` (without `--no-reload`) strips environment variables injected by Docker Compose (`APP_KEY`, `DB_*`) from the server process, causing `MissingAppKeyException` and HTTP 500. The `CMD` therefore must include `--no-reload`.
- **`db` service:**
  - Image: `mysql:8.4`.
  - Persistent volume: `mysql_data:/var/lib/mysql`.
  - Healthcheck: `mysqladmin ping`.

### PHPUnit Test Environment (`phpunit.xml`)
- Uses `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.
- Uses `SESSION_DRIVER=array`, `CACHE_STORE=array`, `MAIL_MAILER=array`, `BCRYPT_ROUNDS=4`.
- Tests use `RefreshDatabase` to migrate the in-memory SQLite schema cleanly per test without touching the MySQL `mysql_data` volume.

---

## 7. Implementation Status, Incomplete Requirements & Dependencies

| Use Case | Implemented & Verified | Incomplete / Remaining Work |
| :--- | :--- | :--- |
| **UC-01 (Homepage)** | `HomeController@index` loads categories and up to 12 in-stock products via `ProductRetrievalService`. `homepage.blade.php` renders banners, `<x-product-card-a>`, and `<x-product-card-b>`. | Remove unused hardcoded `$productCategories` array in `HomeController.php` (lines 18–33). Fix null-safe image access in `product-card-a.blade.php` (`$firstImage?->path`). Add dedicated feature tests. |
| **UC-02 (Categories)** | `CategoryController@show` queries category by `slug` (`firstOrFail()`) and fetches products across child categories via `getAllCategoryIds()`. `categories/pages/show.blade.php` renders breadcrumbs, subcategories, and products. | Fix duplicate route in `routes/web.php`: remove typo `/categoriess/{slug}` and static closure on `/categories/{slug}` so `/categories/{slug}` points to `CategoryController@show`. Fix `@if ($products->isNotEmpty())` in `categories/pages/show.blade.php` (line 102 currently uses `@if ($products)` which is always true for an empty Eloquent `Collection`). Fix relative subcategory link `href="{{ route('categories.show', $childCategory->slug) }}"`. Add feature tests. |
| **UC-03 (Classification)** | Recursive parent/child category tree is implemented in `categories` migration, `Category` model, and `ProductCatalogQuery::getAllCategoryIds()`. | Wire classification filter buttons on homepage/category pages to actual category/filter URLs and add feature tests. |
| **UC-04 (Product Search - P0)** | `ProductSearchController@index` validates `q` (`nullable\|string\|max:100`), trims whitespace, queries `name` and `description` via `ProductCatalogQuery::searchProducts()`, preserves keyword in `<x-nav-bar>`, shows result count, `Clear Search` button, `Out of Stock` badge, and `No products found` empty state in `products/pages/index.blade.php`. Covered by 9 tests in `tests/Feature/ProductSearchTest.php`. | Uncommitted changes on `feature/uc04-product-search` need final test verification and review before committing/PR. Consider escaping `%` and `_` wildcards in `LIKE` queries if literal matching is desired. |
| **UC-05 (Filter & Sort)** | Not yet implemented. | Extend `GET /products` validation and `ProductCatalogQuery` to support category filter, price range (`min_price`, `max_price`), stock filter, and allowlisted `sort` options while preserving query parameters across search/filter/pagination. |
| **UC-06 (Product Detail)** | `ProductController@show` handles `{product}/{slug?}` binding and canonical slug redirect. `products/pages/show.blade.php` displays images, title, description, formatted price, stock count/status, rating summary, and breadcrumbs. | Render vendor store name/link and category details more explicitly; add image placeholder fallback when `$product->images` is empty; add feature tests. |
| **UC-07 (Vendor Store)** | Not yet on `feature/uc04-product-search`, but remote branch `origin/feature/uc07-vendor-storefront-and-products` exists (owned by Khang). | Inspect/coordinate with Khang's `feature/uc07-vendor-storefront-and-products` branch; ensure `Vendor::products()` relationship and vendor store link on product detail page are connected. |
| **UC-08 (Related Products)** | "Similar products" section is rendered on `products/pages/show.blade.php` using `ProductCatalogQuery::getSimilarProducts($productId)`. | Update `getSimilarProducts()` to filter by the current product's `category_id`, add a sensible `limit` (e.g., 6 or 12), and handle empty states cleanly in `products/pages/show.blade.php`. |

---

## 8. Test Commands & Verified Results

### Commands
```powershell
# Run full test suite in Docker
docker compose exec -T app php artisan test

# Run UC-04 Product Search feature tests in Docker
docker compose exec -T app php artisan test --filter=ProductSearchTest

# Run locally if PHP 8.3+ with pdo_sqlite is installed on host
php artisan test --filter=ProductSearchTest
```

### Verified Test Inventory & Execution Results
- **Verified Execution (`php artisan test --filter=ProductSearchTest`):**
  - **Result:** `PASSED` — **9 tests, 9 passed, 22 assertions** (`ProductSearchTest.php`).
- `tests/Feature/ProductSearchTest.php` — 9 feature tests covering UC-04:
  1. `test_products_page_loads_successfully`
  2. `test_search_finds_matching_product_name`
  3. `test_search_finds_matching_description`
  4. `test_search_returns_no_results_message`
  5. `test_empty_keyword_returns_products`
  6. `test_long_keyword_is_rejected`
  7. `test_search_keyword_is_escaped_in_html`
  8. `test_search_displays_out_of_stock_badge`
  9. `test_clear_search_link_is_visible_when_keyword_present`
- `tests/Feature/ExampleTest.php`, `tests/Feature/ProfileTest.php`, `tests/Feature/Auth/*Test.php`, `tests/Unit/ExampleTest.php` — Starter/Breeze-style auth & profile tests (note: several auth tests reference Volt/Breeze routes that are currently commented out in `routes/auth.php`).

---

## 9. Important Technical Decisions & Unresolved Questions

### Technical Decisions
1. **Keep Catalog Queries in `ProductCatalogQuery`:** All catalog read operations (homepage, category listing, search, filter/sort, related products) belong in `App\Queries\Products\ProductCatalogQuery` so eager loading and rating aggregates remain consistent.
2. **Preserve `app/View/` Controller Namespace for Existing Catalog Controllers:** Since `routes/web.php` and `routes/products.php` import `HomeController`, `ProductController`, `ProductSearchController`, and `CategoryController` from `App\View`, keep them in place to avoid unnecessary merge conflicts across feature branches.
3. **Bootstrap 5 CDN + Reusable Blade Components:** Maintain `<x-product-card-b>` and `<x-nav-bar>` as the standard catalog UI building blocks without requiring a Node/Vite build step.

### Unresolved Questions to Confirm with the Team
1. **Vendor Visibility Rule (`vendors.status`):** Should catalog listings, search results (`UC-04`), and vendor store pages (`UC-07`) filter out products whose vendor has `status !== 'approved'`, or display all seeded products regardless of vendor status?
2. **Exact Filter & Sort Options for `UC-05`:** Which exact query parameter names and sort keys are agreed upon for `GET /products` (e.g., `category`, `min_price`, `max_price`, `in_stock`, `sort=newest|price_asc|price_desc|rating_desc`)?
3. **Pagination vs. Limit on Catalog Pages:** `ProductCatalogQuery::searchProducts()` currently uses `->limit(100)->get()`. Should `UC-04`/`UC-05` switch to `->paginate(12)->withQueryString()` when `UC-05` is implemented, or keep `Collection` output so existing tests remain unchanged?
