# LarpShop — Agent Instructions & Conventions

> **Mandatory Pre-Read Requirement:** Before starting substantial feature development, refactoring, or security reviews, you **MUST** read:
> 1. [`docs/PROJECT_CONTEXT.md`](docs/PROJECT_CONTEXT.md) — Verified project architecture, route/controller/query/model mappings, UC-01..UC-08 status, and environment setup.
> 2. [`docs/PENTEST_WORKFLOW.md`](docs/PENTEST_WORKFLOW.md) — Authorized feature-by-feature security review workflow, finding template, and safety rules.

---

## 1. Project Purpose, Stack & Feature Scope

- **Project:** **LarpShop** — a multi-category, multi-vendor e-commerce web application built as a university Web Programming course final project (`https://github.com/pillotop/LarpShop`).
- **Core Stack:**
  - **Backend:** PHP `^8.3` (Docker runtime: `php:8.4-cli`), Laravel `^13.17`, `diglactic/laravel-breadcrumbs` `^10.1`.
  - **Database:** MySQL `8.4` in Docker Compose (`db` service) for local development; SQLite in-memory (`:memory:`) for PHPUnit tests.
  - **Frontend:** Server-rendered Blade views and reusable components (`resources/views/components/`), Bootstrap `5.3.8` + Bootstrap Icons `1.11.3` via CDN, and vanilla JS/CSS in `public/js/` and `public/css/`. Do not replace the frontend stack with React/Vue or require a Node build step for base styles.
  - **Testing & Linting:** PHPUnit `^12.5.12` (`php artisan test`), Laravel Pint (`vendor/bin/pint`), PHPCS (`vendor/bin/phpcs`).
- **Confirmed Product-Discovery Scope (UC-01 to UC-08):**
  - `UC-01` Homepage & Featured Products (`P0`)
  - `UC-02` Browse Product Categories (`P0`)
  - `UC-03` Browse Products by Classification (`P0`)
  - `UC-04` Product Search by Name or Keyword (`P0` — priority feature)
  - `UC-05` Filter and Sort Products (`P1`)
  - `UC-06` Product Detail Page (`P0`)
  - `UC-07` Vendor Store Pages (`P1`)
  - `UC-08` Related Products (`P2`)
- **Scope Control:** Do not invent unrequested enterprise layers, external payment gateways, or recommendation engines. Preserve existing cart, auth, and vendor code even when working strictly on UC-01..UC-08.

---

## 2. Team Roles & Collaboration Rules

### Contributors
- **Việt (`V`):** Main backend/application developer, system architecture, database schema, authentication/authorization, and core order/cart workflows. Reviews database, permission, and core search/order PRs.
- **Trí (`T`):** AI-assisted feature developer (Catalog discovery UC-01..UC-06, UC-08, customer order views) and authorized security tester (Web Pentest, PoC evidence collection, remediation verification).
- **Khang (`K`):** AI-assisted feature developer (Vendor storefront UC-07, vendor/admin management features) and functional/security tester.

### Working-Tree & Git Safety Rules
1. **Inspect before editing:** Always run `git status --short` and check the current branch before making changes.
2. **Preserve uncommitted work:** Never revert, overwrite, or delete modified or untracked files created by teammates or previous sessions without explicit user permission.
3. **No unsolicited Git mutations:** Do not run `git commit`, `git push`, `git reset`, `git clean`, `git checkout`, or switch/merge branches unless explicitly instructed by the user.
4. **No destructive environment commands:** Never run `docker compose down -v`, drop persistent MySQL volumes (`mysql_data`), or run `migrate:fresh` / `db:wipe` against the persistent development database.
5. **No secret leakage:** Never copy `.env` secrets, passwords, or tokens into documentation, logs, commits, or Docker images.

---

## 3. Language Rules

- **English-Only Code & Technical Artifacts:** All PHP code, class/method/variable names, code comments, Blade UI text, validation/error messages, commit messages, and technical documentation in the repository **must be written in English**.
- **Vietnamese Explanations for the User:** Explain architectural choices, code changes, debugging steps, and test results to the student developer in **clear, beginner-friendly Vietnamese**.

---

## 4. Existing Architecture & Coding Conventions

LarpShop follows a pragmatic, layered Laravel MVC structure:

```text
Routes (routes/web.php, routes/products.php, routes/auth.php)
  → Controllers (app/View/*Controller.php & app/Http/Controllers/*Controller.php)
  → Request Validation ($request->validate / Form Requests)
  → Queries / Services (app/Queries/Products/ProductCatalogQuery.php, app/Services/*)
  → Eloquent Models (app/Models/*)
  → Blade Views & Reusable Components (resources/views/*)
```

### Actual Repository Conventions to Respect
- **Controller Locations:**
  - Page-rendering catalog controllers currently reside in `app/View/` (`HomeController`, `ProductController`, `ProductSearchController`, `CategoryController`) under namespace `App\View`.
  - Action/JSON/Auth controllers reside in `app/Http/Controllers/` (`CartController`, `ProductImageController`, `Auth/*`).
  - Do not move existing controllers across namespaces unless explicitly requested.
- **Catalog Query Centralization:**
  - Keep database filtering, search (`LIKE`), eager loading (`with`), review aggregates (`withAvg`, `withCount`), sorting, and limits inside `App\Queries\Products\ProductCatalogQuery` rather than in controllers or Blade templates.
  - Never load full tables into PHP memory to filter them in application code.
- **Shared View Data:**
  - `App\Providers\AppServiceProvider` registers `App\View\Composers\ProductCapabilityComposer` to inject `$cart` into catalog views (`homepage`, `products.pages.index`, `products.pages.show`, `categories.pages.show`). When adding new catalog views that render `<x-nav-bar>`, register them in `AppServiceProvider` or pass `:cart="$cart"`.
- **Custom Primary Keys (All 10 Models):**
  Every Eloquent model uses a custom primary key instead of `id`. Always verify the PK column in queries, foreign keys, and route bindings:

  | Model | Table | PK Column |
  | :--- | :--- | :--- |
  | `User` | `users` | `user_id` |
  | `Vendor` | `vendors` | `vendor_id` |
  | `Category` | `categories` | `category_id` |
  | `Product` | `products` | `product_id` |
  | `ProductImage` | `product_images` | `product_image_id` |
  | `ProductReview` | `product_reviews` | `review_id` |
  | `Order` | `orders` | `order_id` |
  | `OrderDetail` | `order_details` | `order_detail_id` |
  | `Cart` | `carts` | `cart_id` |
  | `CartItem` | `cart_items` | `cart_item_id` |

- **Route Model Binding:**
  `Route::get('/products/{product}/{slug?}')` binds `{product}` by `product_id`. If `{slug}` does not match `$product->slug`, `ProductController::show` redirects to the canonical URL.
- **Security by Default:**
  - Validate all user inputs (`q`, filters, sort keys, IDs) on the server.
  - Enforce explicit allowlists for dynamic sorting columns and directions.
  - Escape all untrusted output in Blade using `{{ ... }}`.

---

## 5. Quick Commands

```powershell
# Run automated tests inside Docker (recommended)
docker compose exec -T app php artisan test

# Run automated tests locally (uses SQLite :memory: via phpunit.xml)
php artisan test
php artisan test --filter=ProductSearchTest

# Format & check code style
vendor/bin/pint
vendor/bin/phpcs
```
