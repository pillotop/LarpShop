# LarpShop — Demo Data Seeding Guide

> **Scope & Safety Notice:** Demo seeding uses deterministic `.test` accounts, synthetic payment provider references, and local product image assets. It never sends real emails, charges real payment gateways, bulk-verifies pre-existing accounts, or calls `migrate:fresh` / `db:wipe`.

---

## 1. Entry Point & Execution Order

The single canonical entry point is `Database\Seeders\DatabaseSeeder`:

```bash
php artisan db:seed
```

Inside Docker Compose (isolated or dedicated dev DB):

```bash
docker compose exec -T app php artisan db:seed
```

`DatabaseSeeder::run()` executes seven idempotent steps in strict dependency order:

| Step | Seeder Class | Responsibility | Stable Key / Idempotency Strategy |
| :---: | :--- | :--- | :--- |
| 1 | `CategorySeeder` | 6 parent categories & 30 child categories matching homepage banners & localized slugs | `categories.slug` (`updateOrCreate`) |
| 2 | `DemoUserAndVendorSeeder` | 2 admin accounts, 51 customer accounts (`.test` + core demo users), 12 vendor stores, multi-store owner (`volttech`), onboarding applications across `draft`/`submitted`/`approved`/`rejected` + legacy pending vendor | `users.email` / `users.username`, `vendors.store_name`, `vendor_applications.vendor_id` |
| 3 | `DemoCatalogSeeder` | ~162 multi-category products, active `ProductVariant` rows, local `ProductImage` rows, stock bands (`healthy > 5`, `low_stock 1..5`, `out_of_stock 0`), and `pending`/`rejected`/`is_active=false` items | `products.slug`, `product_variants.sku`, `(product_id, sort_order)` |
| 4 | `DemoTransactionSeeder` | ~280 orders across a 180-day horizon, `OrderAddress` snapshots, `Payment` & `PaymentEvent` lifecycles (real-sandbox vs simulated providers, retries, pending, cancelled, expired), and `OrderDetail` fulfillment states | `order_addresses.address_line_2` (`DEMO-ORD-XXXX`), `payments.provider_reference` (`DEMO-PAY-*`) |
| 5 | `DemoCustomerActivitySeeder` | Saved `Address` records, `wishlist_items`, `carts`/`cart_items`, and verified-buyer `product_reviews` (strictly post-purchase, `<= 1` per `(user_id, product_id)`) | `(user_id, address_line_1)`, `syncWithoutDetaching`, `(user_id, product_id)` |
| 6 | `PromoSectionSeeder` | Homepage `Featured finds` curated `ProductCollection` & `PromoSection` | `product_collections.slug` (`featured-finds`) |
| 7 | `BroadSelectSeeder` | Homepage `New arrivals` & themed discovery collections | `promo_sections.placement` & `product_collections.slug` |

> **Legacy Standalone Seeders:** `UserSeeder`, `VendorSeeder`, and `ProductSeeder` remain callable as backwards-compatible entry points that delegate cleanly to the idempotent seeders above.

---

## 2. Demo Accounts Reference

All demo accounts share password **`password123`** (except pre-existing production-like test fixtures left untouched):

| Role / Scenario | Email | Username | Notes |
| :--- | :--- | :--- | :--- |
| **Platform Admin** | `admin@admin.com` | `admin` | Primary admin console & moderation account |
| **Demo Admin** | `demo-admin@example.test` | `demo_admin` | Secondary `.test` admin account |
| **Primary Demo Customer** | `customer@example.test` | `demo_customer` | Verified customer with addresses, orders, wishlist & reviews |
| **Pending Order / Active Buyer** | `john@example.com` | `johndoe` | Holds the canonical `Pending` order & purchase history |
| **Verified Paid Buyer** | `jane@example.com` | `janestewart` | Holds canonical `Paid` order with single succeeded payment |
| **Demo Customer Cohort** | `demo.customer.01@example.test` .. `demo.customer.46@example.test` | `demo_*_01..46` | Repeat & first-time customers (`45..46` intentionally unverified) |
| **Primary Demo Vendor** | `vendor@example.test` | `demo_vendor` | Active storefront: *Demo Vendor Store* |
| **Multi-Store Owner** | `contact@volttech.example.test` | `volttech` | Owns *VoltTech Gadgets* (`active`), *VoltTech Outlet* (`approved`), and legacy pending *VoltTech Workshop* |
| **Specialty Store Owners** | `support@audiopulse.example.test`, `sales@cybergear.example.test`, `info@nextgen.example.test`, `hello@smarthome.example.test`, `store@peakfit.example.test`, `sales@trailblazer.example.test`, `care@hearthhaven.example.test`, `studio@luxeloom.example.test` | `audiopulse`, `cybergear`, `nextgen`, `smarthome`, `peakperformance`, `trailblazer`, `hearthhaven`, `luxeloom` | Dedicated storefronts spanning electronics, sports, home, beauty & crafts |
| **Onboarding Backlog / Review Cases** | `nordic.applicant@example.test`, `urbancycle.applicant@example.test`, `artisanglass.applicant@example.test`, `atelierdraft.applicant@example.test` | `*_applicant` | Demonstrates `submitted` (2), `rejected` (1), and `draft` (1) applications |

---

## 3. Seeded Scenario & Report Coverage

- **Shared Time Anchor:** `DemoSeedContext` anchors timelines to a single consistent reference timestamp (`config('seeding.anchor') ?? now()->startOfHour()`), spanning **180 days (6 months)** with activity distributed across `7_days`, `30_days`, `this_month`, `previous()` comparison windows, and monthly history (`all`).
- **Payments & Providers:**
  - *Provider (sandbox/real-gateway schema, `simulated = false`):* `paypal`, `stripe`, `vnpay`, `momo`, `zalopay` (including VND `source_currency` + `25000` `exchange_rate` quotes alongside USD base `orders.total_amount`).
  - *Simulated (`simulated = true`):* `fake`, `momo_demo`, `zalopay_demo`, `vnpay_demo`.
  - Approximately 18% of `Paid` orders include a failed initial attempt followed by a retry `Succeeded` payment, with full `PaymentEvent` trails and zero undated succeeded payments.
- **Inventory & Fulfillment:**
  - `InventoryReportQuery` (`LOW_STOCK_THRESHOLD = 5`) populates `out_of_stock` (`0`), `low_stock` (`1..5`), `healthy_stock` (`> 5`), `pending_products`, plus `rejected` and `is_active = false` items.
  - `FulfillmentReportQuery` populates `unfulfilled`, `processing`, `shipped`, and `delivered` lines, plus older `unfulfilled`/`processing` paid orders for aging backlog visibility.
- **Reviews:** Every review belongs to a customer with a `Paid` order containing that approved product, is timestamped strictly after `completed_at`, covers ratings `1..5` (including recent 1–2 star reviews for `ReviewReportQuery`), leaves ~22% of catalog items unreviewed, and marks review copy with `[Demo Review]`.

---

## 4. Traffic & Page-View Analytics (Deferred Scope)

LarpShop does not currently store page-view or storefront traffic events. Per project guidelines, **do not fabricate traffic or conversion rates from orders, payment attempts, or session rows**. If traffic reporting is added in a future phase, define it as a dedicated work item covering:
1. Event schema (`page_viewed`, `product_viewed`, `checkout_started` with anonymous session hash, path, referrer, timestamp).
2. Asynchronous or sampled ingestion with bot/crawler filtering.
3. Exact metric definitions (Unique Visitors, Sessions, Product Detail Conversion = Paid Orders / Product Sessions) in a dedicated `TrafficReportQuery`.
