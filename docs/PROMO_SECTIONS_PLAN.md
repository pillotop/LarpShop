# Promo sections: design and implementation

Status: Stages 1-5 approved; Stage 6 completion checks implemented for final review.

## Scope and verified starting point

- First integration target: the public homepage, managed by platform admins.
- Three preset layouts: product grid, horizontal product row, and image beside
  products. No live page editor, arbitrary HTML, pricing rules, or automatic
  favorites ranking in this iteration.
- `HomeController` retrieves one product list that the homepage currently repeats
  in its carousel and grid. Its category image mosaic is hardcoded Blade content.
  Existing homepage content remains the fallback when no promo sections are visible.
- Product selection and review aggregates already belong in `ProductCatalogQuery`.
  Existing product cards include wishlist, cart controls, and display currency.
- Admin routes already use `auth` and `admin` middleware with custom Blade pages.
- Banners currently require a vendor owner and support only `vendor_header`.
  They are not a platform-wide content management system.
- The two-arrow scroller scopes its queries to a wrapper but uses repeated IDs.
  Multiple new sections should use data attributes scoped to each wrapper.
- The context documents describe an older stack. The implementation will follow
  the actual Vite, Bootstrap, Alpine, localization, and MySQL test setup in this branch.

## Responsibilities

1. Product collection: a reusable, manually ordered set of products with its own
   public listing page. It does not define a homepage layout or change prices.
2. Promo section: a published placement of a collection, choosing presentation,
   optional promotional image, display order, and scheduling.
3. Layout component: renders prepared data. No database queries or authorization
   logic inside Blade; no request-supplied component or template names.

Admin-authored titles and descriptions follow the current product content model:
plain text, escaped on output, not automatically translated. Interface labels and
validation messages have English and Vietnamese translation keys.

## Proposed data model

- `product_collections`: `product_collection_id`, name, unique slug, description,
  published flag, timestamps.
- `product_collection_items`: collection and product foreign keys, `sort_order`,
  a unique collection/product pair. Product deletion removes its membership.
- `promo_sections`: `promo_section_id`, collection foreign key, optional heading
  and introductory text, allowlisted placement and layout, image metadata when
  needed, active flag, `starts_at`, `expires_at`, `sort_order`, timestamps.

Only `homepage_main` is a supported placement initially. It represents one ordered
insertion area after the existing category mosaic, before the existing discovery
sections. More page placements can be explicitly registered later.

Use new migrations rather than editing existing vendor banner migrations. Block
collection deletion while sections reference it and let the admin detach those
sections first. Do not add a fake vendor owner to platform sections.

The collection published flag controls direct public access. Section scheduling
controls homepage placement independently: a published collection can remain
accessible while its homepage promotion is not running. Draft previews require
admin authorization and do not make draft content publicly accessible.

## Query and rendering flow

Homepage controller -> PromoSectionQuery -> scheduled section records and their
published collections -> bounded product queries through ProductCatalogQuery ->
prepared section data -> allowlisted Blade layout.

The collection controller uses the same product eligibility and ordering, with
pagination for the public collection page. Section preview and public rendering
use the same preparation and layout code; only draft access differs.

- Public products must be approved and active, and belong to an active/approved
  vendor. Current catalog queries do not all check vendor status; apply this rule
  to the new promo/collection queries without silently rewriting unrelated flows.
- Homepage sections omit out-of-stock products and disappear when no eligible
  products remain. Collection pages may show out-of-stock eligible products with
  the existing unavailable state. Hidden/rejected/deleted products never appear.
- Product order follows collection membership order with a stable product-key tie
  breaker. Layout limits are server-defined, not unlimited admin inputs.
- Eager-load the relations product cards use, including category, images, vendor,
  and review aggregates. Do not load all reviews just to render aggregates.
- Start time is inclusive; expiry is exclusive. Validate expiry after start and
  use the configured application timezone, made explicit in admin forms.
- Initially query directly with bounded section/product counts. Introduce cache
  only after checking query costs and all product/moderation/schedule invalidations.

## Reuse and adaptations

- Reuse product cards, rating display, wishlist/cart actions, money formatter,
  grid, and the existing admin layout.
- Adapt the scroller to wrapper-scoped data attributes when building repeatable
  rows. Preserve existing homepage category scrolling.
- Reuse the cropper and responsive WebP processing mechanics. The current image
  processor takes a banner zone from `config/banners.php`; extract a small shared
  image-profile API before using it for promo images, while retaining the vendor
  banner wrapper and behavior. Do not add homepage content to the vendor banner table.
- Register the new collection page with ProductCapabilityComposer so its navbar
  receives cart and category data.
- Use Alpine only for useful form interactions and preview choices. Server-side
  validation and authorization remain authoritative. New UI uses at most rounded-2.

## Expected files by stage

Names below are proposed and may be adjusted during implementation.

### Stage 2: persistence and queries

- New migrations for the three tables; ProductCollection and PromoSection models.
- `config/promotions.php`: supported placements, layouts, image profiles, limits.
- `app/Queries/Promotions/PromoSectionQuery.php` and collection read query.
- Extend `app/Queries/Products/ProductCatalogQuery.php` for collection products.
- Factories/seeders and feature tests for visibility, membership, order, scheduling.

### Stage 3: layouts and authorized preview

- `resources/views/components/promo-section.blade.php` as the renderer.
- `resources/views/components/promo-layouts/{grid,row,split}.blade.php`.
- Shared section heading partial, scoped styles, and image profile adapter.
- Admin-only preview controller, route, view, and validation.
- Adapt `resources/views/components/two-arrow-scroller.blade.php`, its JavaScript,
  and existing callers to use scoped data attributes where needed.
- Keep new styles/scripts in the existing Vite setup; no additional frontend stack.

### Stage 4: collection management and public pages

- Admin collection controller, Form Requests, management service, and Blade views.
- Collection controller under `app/View/` and public collection Blade view.
- `routes/admin.php`, public routes, breadcrumbs, admin sidebar navigation,
  `AppServiceProvider` composer registration, and English/Vietnamese translations.
- Tests for admin-only actions, validation, ordering, drafts, and public pagination.

### Stage 5: section management and homepage integration

- Admin section controller, Form Requests, management service, and Blade views.
- Reuse preview rendering from Stage 3; add schedule and layout validation.
- `app/View/HomeController.php` and `resources/views/homepage.blade.php`.
- Routes, sidebar navigation, translations, image lifecycle and reorder tests.
- Add focused homepage promo tests for configured and empty states.

### Stage 6: completion

- Responsive/accessibility checks, repeated component instances, image loading,
  query-count review, authorization tests, full suite, frontend build, documentation.
- Cache code only if the measured query cost justifies it; expire at schedule
  boundaries and handle edits, membership changes, stock, moderation, and vendor status.

## Review gates

Finish and report each stage before requesting approval for the next one. Stage 1
added only this design record; Stage 2 was separately approved. Later stages still
require approval. Pricing and existing vendor banner behavior remain outside this work.

## Stage 2 implementation notes

- Added the three migrations, ProductCollection/PromoSection models and factories,
  `config/promotions.php`, collection and section readers, and PromoSectionData.
- `PromoSectionQuery::visibleForPlacement()` returns a collection of prepared
  section/product data. Unknown placements are rejected; unknown stored layouts
  are ignored. Empty/ineligible collections are excluded before the section limit.
- The section cap is eight. Grid/row/split layouts show at most 8/12/4 products;
  collection pages paginate 24 at a time. Images for split layouts will be handled
  in Stage 3 using the registered portrait profile.
- ProductCatalogQuery owns collection eligibility, card eager loading, review
  aggregates, stock filtering, membership order, and product pagination. Its
  existing catalog methods are unchanged. Low-level product reads also support
  draft collections for the later authorized preview flow; callers must use the
  published collection reader for public routes.
- No cache, routes, controllers, forms, or Blade changes have been introduced.
  Form validation for authored schedules/layouts belongs to the later management
  stages. This stage adds no public write endpoint.
- PromoSectionSeeder initializes one published `featured-finds` collection and
  active grid section only when eligible products exist. Re-running it preserves
  existing admin edits. DatabaseSeeder calls it after creating products.
- Run `php artisan migrate` to apply the additive migrations to a development
  database, then optionally `php artisan db:seed --class=PromoSectionSeeder`.
  No fresh migration or data reset is needed. The agent only applies migrations
  in the isolated test database during verification.
- Regression tests live in `tests/Feature/Promotions/`. They cover publication,
  eligibility, exact scheduling, ordering, limits, pagination, eager loading,
  database constraints, factory casts, and non-destructive demo seeding.

Stage 2 verification: 17 new tests; full suite passed with 457 tests and 6,311
assertions against `larpshop_testing`. Pint and whitespace checks passed. No
development database migration or seeding was run.

### Stage 2 changed file inventory

Existing application files extended:

- `app/Queries/Products/ProductCatalogQuery.php`
- `database/seeders/DatabaseSeeder.php`

New application files:

- `app/Models/ProductCollection.php`
- `app/Models/PromoSection.php`
- `app/Data/Promotions/PromoSectionData.php`
- `app/Queries/Promotions/ProductCollectionQuery.php`
- `app/Queries/Promotions/PromoSectionQuery.php`
- `config/promotions.php`
- `database/factories/ProductCollectionFactory.php`
- `database/factories/PromoSectionFactory.php`
- `database/seeders/PromoSectionSeeder.php`
- `database/migrations/2026_11_18_000001_create_product_collections_table.php`
- `database/migrations/2026_11_18_000002_create_product_collection_items_table.php`
- `database/migrations/2026_11_18_000003_create_promo_sections_table.php`

Tests added:

- `tests/Feature/Promotions/ProductCollectionQueryTest.php`
- `tests/Feature/Promotions/PromoSectionQueryTest.php`

This plan is the only documentation file updated.

## Stage 3 implementation notes

- Admin preview: `GET /admin/promotions/preview` (`admin.promotions.preview`).
  Optional validated query parameters are `collection`, `section`, and `layout`.
  Guests must sign in and non-admin users are denied before preview validation.
- The picker shows the latest 50 collections, including drafts. An explicitly
  selected older collection is included in the picker too. No draft is published
  and no preview selection is saved. Preview can render an existing section's
  image, heading, and introduction while temporarily trying another layout.
- Full/narrow preview controls use Alpine. CSS container queries respond to the
  actual section width, including inside the narrow preview frame. This is a
  layout preview, not a complete emulation of a mobile browser/device.
- `PromoSectionQuery` now prepares both preview and public results with the same
  product query. Preview bypasses publication/scheduling only; product moderation,
  stock, and vendor eligibility still apply. Public placement queries are unchanged.
- `promo-section` resolves its layout through server-owned configuration and uses
  unique heading IDs. Grid, row, and split layouts render the existing product card.
  Product cards are inert in admin previews; scroller controls remain interactive.
- A renderer may receive a trusted `viewAllUrl`. The preview omits that link until
  the public collection route is implemented in Stage 4; no broken route is added.
- Split layout supports stored responsive imagery or a neutral placeholder. The
  upload management form remains Stage 5 work. `PromoImageProcessor` is the backend
  adapter ready for that form, using the portrait profile registered in Stage 2.
- Extracted CroppedImageProfile/CroppedImageProcessor and `responsive-image` from
  vendor banner code. BannerImageProcessor retains its public `store(file, zone)`
  API, vendor upload settings, crop checks, WebP variants, and cleanup behavior.
  Promo image settings and storage paths are separate from vendor banner settings.
- Scroller selectors now use wrapper-scoped data attributes across all three
  existing callers. Scroll distance uses the visible width, respects reduced
  motion, and missing markup does not abort other instances. Icon dimensions are
  local to the component, and transparent controls work with dark/light backgrounds.
- No homepage promo placement, admin CRUD, migration, or development seeding was
  performed in Stage 3. Existing homepage/category/product templates changed only
  to update their scroller content selector.

Stage 3 verification: full suite passed with 471 tests and 6,408 assertions;
all five frontend test files, the Vite production build, Pint, and whitespace
checks passed. Browser checks used in-memory fixtures on a temporary local page
to inspect grid/row/split layouts and narrow-width scrolling without development
database writes. Stored image/srcset rendering is also covered by feature tests.

### Stage 3 changed file inventory

Preview flow and preparation:

- `app/Http/Controllers/Admin/PromoSectionPreviewController.php` (new)
- `app/Http/Requests/Promotions/PreviewPromoSectionRequest.php` (new)
- `app/Queries/Promotions/ProductCollectionQuery.php`
- `app/Queries/Promotions/PromoSectionQuery.php`
- `routes/admin.php`
- `config/promotions.php`
- `lang/en/promotions.php` and `lang/vi/promotions.php` (new)
- `resources/views/admin/promotions/preview.blade.php` (new)

Rendering and shared assets:

- `resources/views/components/promo-section.blade.php` (new)
- `resources/views/components/promo-layouts/heading.blade.php` (new)
- `resources/views/components/promo-layouts/grid.blade.php` (new)
- `resources/views/components/promo-layouts/row.blade.php` (new)
- `resources/views/components/promo-layouts/split.blade.php` (new)
- `resources/css/components/promo_sections.css` (new)
- `resources/views/components/two-arrow-scroller.blade.php`
- `resources/js/components/two_arrow_scroller.js`
- `resources/css/components/two_arrow_scroller.css`
- `resources/views/homepage.blade.php`
- `resources/views/categories/pages/show.blade.php`
- `resources/views/products/pages/show.blade.php`

Image reuse:

- `app/Data/Images/CroppedImageProfile.php` (new)
- `app/Services/Images/CroppedImageProcessor.php` (new)
- `app/Services/Promotions/PromoImageProcessor.php` (new)
- `app/Services/Banners/BannerImageProcessor.php`
- `resources/views/components/responsive-image.blade.php` (new)
- `resources/views/components/banner-image.blade.php`

Tests:

- `tests/Feature/Promotions/PromoSectionPreviewTest.php` (new)
- `tests/Feature/Promotions/PromoImageProcessorTest.php` (new)
- `tests/Frontend/two_arrow_scroller.test.mjs` (new)
- `tests/Feature/Vendor/BannerImageProcessorTest.php` (generic storage-error wording)

This document tracks the Stage 3 changes in addition to the Stage 2 inventory above.

## Stage 4 implementation notes

- Admin entry: `/admin/collections`, also linked in the admin sidebar and preview.
  Collection metadata, product membership, and product ordering have separate
  validated actions and forms; updating metadata does not replace membership.
- Metadata includes name, slug, description, and publication. A blank slug is
  generated from the name; slugs must be unique lowercase URL segments. Updating
  a slug changes the public URL; old URLs are not redirected automatically.
- Admin collection listing and product search are paginated. Product search uses
  product/store name, excludes existing members and ineligible products, and can
  include out-of-stock products. Management prices are explicitly USD.
- Collection membership is capped at a configurable 100 products. Add/remove/move
  operations lock the collection row inside a transaction. Repeated adds are
  idempotent; moves normalize tied positions and preserve stable ordering. Member
  actions verify that the product belongs to the selected collection. Mutations
  touch the collection timestamp. Removing a member never deletes the product.
- Collection deletion is blocked with a validation message while a section
  references it. Otherwise memberships cascade away and products remain intact.
- Public entry: `/collections/{slug}` (`collections.show`). Drafts and unknown
  slugs return 404. Product ordering/pagination and eligibility use the existing
  collection product query; out-of-stock eligible products show their unavailable
  state. The public page has a navbar/cart, wishlist, currency display, breadcrumbs,
  and an empty state. It is registered with ProductCapabilityComposer.
- Public promo renderers now supply a default View all link for published
  collections. Admin preview still omits that link unless explicitly supplied.
- Templates are separated into metadata, membership, and picker partials. No
  new JavaScript state or dependencies were needed. New collection/admin preview
  pages opt into a light theme through a layout section because the existing
  admin CSS fixes its background to light. Other admin pages retain their
  original theme markup and behavior.
- Homepage placement and section CRUD remain Stage 5 work. No new migration or
  development database writes were needed for this stage.

### Stage 4 changed file inventory

New backend files:

- `app/Http/Controllers/Admin/AdminProductCollectionController.php`
- `app/Http/Controllers/Admin/CollectionProductController.php`
- `app/View/ProductCollectionController.php`
- `app/Http/Requests/Promotions/AdminPromotionRequest.php` (renamed in Stage 5)
- `app/Http/Requests/Promotions/SaveProductCollectionRequest.php`
- `app/Http/Requests/Promotions/CollectionSearchRequest.php`
- `app/Http/Requests/Promotions/AddCollectionProductRequest.php`
- `app/Http/Requests/Promotions/MoveCollectionProductRequest.php`
- `app/Services/Promotions/ProductCollectionManagementService.php`

Backend/configuration extended:

- `app/Queries/Promotions/ProductCollectionQuery.php`
- `app/Queries/Products/ProductCatalogQuery.php`
- `app/Providers/AppServiceProvider.php`
- `config/promotions.php`
- `routes/admin.php`
- `routes/web.php`
- `lang/en/promotions.php` and `lang/vi/promotions.php`

New views:

- `resources/views/admin/collections/index.blade.php`
- `resources/views/admin/collections/create.blade.php`
- `resources/views/admin/collections/edit.blade.php`
- `resources/views/admin/collections/partials/form.blade.php`
- `resources/views/admin/collections/partials/members.blade.php`
- `resources/views/admin/collections/partials/product-picker.blade.php`
- `resources/views/collections/pages/show.blade.php`

Existing views extended:

- `resources/views/components/admin/side-panel.blade.php`
- `resources/views/components/promo-section.blade.php`
- `resources/views/admin/promotions/preview.blade.php`
- `resources/views/layouts/admin.blade.php`

New tests:

- `tests/Feature/Promotions/AdminCollectionManagementTest.php`
- `tests/Feature/Promotions/PublicCollectionTest.php`

Browser review used disposable fixtures in `larpshop_testing`, rendered into
temporary local HTML files. No real account credentials or development records
were used. The public static HTML cannot serve cart API requests; functional cart
integration is verified by the HTTP feature tests instead.

Stage 4 verification: 15 new tests; full suite passed with 486 tests and 6,629
assertions. All five frontend test files, Vite production build, Pint, and
whitespace checks passed. Browser review covered the metadata form, member
controls, product picker, and public collection page.


## Stage 5 implementation

- Admin entry: `/admin/promo-sections`, available through the admin sidebar.
  Sections have separate create/edit, activation, movement, and deletion actions.
  All routes require both authentication and the admin role; Form Requests also
  enforce admin authorization and validate submitted fields.
- Select a collection, one of the three configured layouts, and `homepage_main`.
  The editor lists the 50 newest collections plus the selected older collection.
  Any collection's edit page also links directly to creating its promo section.
  Invalid old selections preserve the validation page instead of causing a 404.
- Optional heading and introduction fall back to collection content. Admin text
  remains escaped plain text. Active status is distinct from actual visibility:
  collection publication, scheduling, stock, and moderation still govern display.
- Scheduling uses `config('app.timezone')`, explicitly shown in the form/list.
  Blank dates leave that boundary open. Starts are inclusive and expiry exclusive;
  a supplied expiry must follow a supplied start.
- Reuse the image cropper for the split layout's configured portrait profile.
  Alpine shows and enables image fields only for a layout that uses them; switching
  layouts retains local field values. A disabled cropper cannot block submission
  because of an unconfirmed source image. Select controls, responsive columns,
  and checkboxes use the installed Bootstrap 6 API.
- Server-side image validation requires a confirmed PNG/WebP crop with exact
  profile dimensions, configured file size, and alternative text. Storage paths,
  disk names, variant metadata, and sort positions cannot be set by request data.
  Existing images are retained by default. A split layout cannot remove its image
  without a valid replacement; changing to an image-free layout allows removal.
- Section writes and moves use transactions and row locks. New sections append
  to their placement; up/down movement normalizes tied positions with a stable
  primary-key order. A configurable 100 managed sections per placement bounds
  this management flow; the public homepage still displays at most eight.
- Replacement/deletion removes previous image files only after database commit.
  Failed saves remove newly generated files while preserving previous metadata
  and images. Deleting a section keeps its collection, memberships, and products.
- `HomeController` obtains prepared section data through `PromoSectionQuery`.
  Homepage renders it after the category mosaic and before existing discovery
  content, using the same allowlisted renderer as preview. Existing homepage
  content remains present when no sections qualify. No caching was introduced.
- No new dependency, migration, or persistent development database write was
  required for Stage 5. Stage 6 performance/responsive/accessibility review remains
  a separate approval gate.

### Stage 5 changed file inventory

New backend files:

- `app/Http/Controllers/Admin/AdminPromoSectionController.php`
- `app/Http/Requests/Promotions/SavePromoSectionRequest.php`
- `app/Http/Requests/Promotions/SelectPromoCollectionRequest.php`
- `app/Http/Requests/Promotions/TogglePromoSectionRequest.php`
- `app/Http/Requests/Promotions/MovePromoSectionRequest.php`
- `app/Services/Promotions/PromoSectionManagementService.php`

The shared authorization request was renamed from `AdminCollectionRequest` to
`AdminPromotionRequest`. Its existing subclasses `AddCollectionProductRequest`,
`CollectionSearchRequest`, `MoveCollectionProductRequest`, and
`SaveProductCollectionRequest` now use the broader name; their rules are unchanged.

Backend/configuration extended:

- `app/Queries/Promotions/ProductCollectionQuery.php`
- `app/Queries/Promotions/PromoSectionQuery.php`
- `app/Http/Controllers/Admin/PromoSectionPreviewController.php`
- `app/View/HomeController.php`
- `config/promotions.php`
- `routes/admin.php`
- `lang/en/promotions.php` and `lang/vi/promotions.php`

New views:

- `resources/views/admin/sections/index.blade.php`
- `resources/views/admin/sections/edit.blade.php`
- `resources/views/admin/sections/partials/form.blade.php`
- `resources/views/admin/sections/partials/fields.blade.php`
- `resources/views/admin/sections/partials/schedule.blade.php`
- `resources/views/admin/sections/partials/image.blade.php`
- `resources/views/admin/sections/partials/actions.blade.php`

Existing views/JavaScript extended:

- `resources/views/homepage.blade.php`
- `resources/views/components/admin/side-panel.blade.php`
- `resources/views/admin/collections/edit.blade.php`
- `resources/views/admin/promotions/preview.blade.php`
- `resources/js/components/image_cropper.js`

New tests:

- `tests/Feature/Promotions/AdminPromoSectionTest.php`
- `tests/Feature/Promotions/PromoSectionImageManagementTest.php`
- `tests/Feature/Promotions/HomepagePromoSectionTest.php`

### Stage 5 verification

- Promo feature suite: 60 tests, 395 assertions, all passed.
- Full PHP suite: 500 tests, 6,822 assertions, all passed (14 tests added in Stage 5).
- All four `tests/Frontend/*.test.mjs` files passed.
- Vite production build, Pint checks on promotion code, and whitespace checks passed.
- Browser review used disposable `larpshop_testing` fixtures rendered into temporary
  static HTML. Verified admin listing, edit form, split-layout image field activation,
  disabled fields for row layout, and retained alternative text when switching back.
  Browser review also caught and corrected obsolete Bootstrap form/grid classes.
  Functional writes and public homepage integration are covered by HTTP tests.


## Stage 6 implementation and review

### Query preparation and cache decision

Public section preparation now batches products across the selected collections.
`ProductCatalogQuery::loadPromoProducts` uses Laravel's limited eager loading on
`BelongsToMany`: the limit applies per collection in SQL, rather than loading all
memberships and filtering them in memory. Products retain membership order with a
product-key tie breaker, eligibility checks, review aggregates, and eager-loaded
category/images/vendor. Each section then takes its configured layout-sized slice.
The default bounds are eight sections and at most twelve products per distinct
collection. Preview and paginated public collection pages retain their existing
queries and authorization boundaries.

Measured on disposable MySQL fixtures, counting only promo preparation queries:

| Scenario | Stage 5 | Stage 6 |
| --- | ---: | ---: |
| One populated collection/section | 6 | 6 |
| Eight sections sharing one collection | 34 | 6 |
| Eight sections using distinct collections | 34 | 6 |
| No eligible section | 1 | 1 |

Rendering prepared data adds zero product queries. These are query counts, not a
production latency guarantee, and exclude existing non-promo homepage queries.
No cache was added: bounded batched reads avoid repeated queries while preserving
immediate stock, moderation, collection, vendor, and scheduling changes. Caching
would require invalidating all those inputs and expiring at schedule boundaries;
current measurements do not justify that additional state.

### Presentation and accessibility

- Container queries use one product column below 22rem, two from 22rem, three
  grid columns from 48rem, and four from 64rem. The split layout changes from
  stacked to side-by-side at 48rem. This follows available section width, including
  the admin's narrow preview, rather than assuming the full viewport is available.
- Each section has a unique heading and named landmark. Repeated rows have unique
  list IDs; both arrow buttons identify their list through `aria-controls`. Lists
  are focusable for native keyboard scrolling, and decorative arrow SVGs are
  hidden from assistive technology. Existing reduced-motion handling is retained.
- Browser testing exposed wishlist controls painting above the overlaid scroller
  arrows. Raising the arrow stacking level fixes their click target in the shared
  scroller without changing scroll behavior.
- Promotional artwork retains explicit dimensions/aspect ratio, responsive source
  widths, alternative text, and lazy loading with automatic fetch priority.
  The shared product-card-b fallback referenced a nonexistent JPEG. It now uses
  a local neutral SVG placeholder; other legacy card variants remain outside this
  change.
- Browser review used isolated fixtures and static HTML at 320, 390, 768, and
  1280px viewport widths. No document-level horizontal overflow was found. Verified
  the one-column narrow layout, two/four-column wider grids, stacked/side-by-side
  split layout, loaded artwork, independent row arrows, and keyboard row scrolling.
  This is a targeted accessibility check, not a full WCAG certification or a
  cross-browser audit. The static fixture does not implement cart/auth endpoints;
  normal integration is covered by application feature tests.

### Stage 6 changed file inventory

- `app/Queries/Products/ProductCatalogQuery.php`
- `app/Queries/Promotions/PromoSectionQuery.php`
- `resources/css/components/promo_sections.css`
- `resources/css/components/two_arrow_scroller.css`
- `resources/views/components/promo-layouts/row.blade.php`
- `resources/views/components/two-arrow-scroller.blade.php`
- `resources/views/components/product-card-b.blade.php`
- `public/images/product-placeholder.svg`
- `tests/Feature/Promotions/PromoSectionPerformanceTest.php`
- `tests/Feature/Promotions/PromoSectionMarkupTest.php`
- `README.md`
- `docs/PROMO_SECTIONS_PLAN.md`

New tests enforce a fixed query budget, zero lazy queries while rendering,
per-collection limits and layout slices, the empty state, unique landmark/control
IDs, keyboard-focusable lists, responsive/lazy artwork, and a real fallback asset.

Stage 6 final verification: full PHP suite passed with 504 tests and 6,883
assertions. The promo suite contains 64 tests. All four frontend test files,
Vite production build, Pint checks across the promotion implementation, and
whitespace checks passed. No persistent development data or Git history was
changed. All six planned stages are implemented.
