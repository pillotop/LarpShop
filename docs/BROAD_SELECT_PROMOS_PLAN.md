# Broad select promo groups

Branch: `feature/broad-select-promos`.
Status: Stages 1-4 reviewed; Stage 5 implemented and verified. All planned stages are complete for final review.

## Scope

Turn the existing compact homepage selector into a reusable presentation for
multiple product groups. Reuse the existing promo section and collection
management flows. Do not introduce a separate promo administration system.

The carousel timing/recycling fixes made immediately before this work belong to
this branch too. They remain uncommitted alongside Stage 1.

## Stages and review gates

1. Extract `x-broad-select`, prepare its data outside Blade, and preserve existing
   choices, cards, layout, sorting, and carousel behavior. Define the group contract.
2. Implement independent server-side product sources: Top rated, New arrivals,
   and published collections. Extend promo section source metadata while preserving
   existing collection-backed sections. Validate eligibility, ordering, and limits.
3. Extend existing promo administration with the `homepage_broad_select` placement,
   source selection, optional collection selection, ordering, activation, scheduling,
   and preview. No new dashboard.
4. Render each group's own product panel and switch panels instead of sorting one
   shared list. Bound initial payloads; no AJAX required for the initial scope.
   Hidden carousels must stop and remeasure when shown.
5. Polish desktop/mobile presentation, keyboard accessibility, empty states,
   reduced motion, theme behavior, and cart/wishlist integration.

Stop after each stage for review. No schema/admin changes are authorized in Stage 1.

## Existing behavior and limitations

The old selector had two hard-coded choices and one homepage product sample.
Trending sales sorts that sample by review count, not actual sales. Top rated
sorts the same sample by rating, review count, and original input order. The
initial product order remains the existing homepage query order until a choice
is clicked. Stage 1 deliberately preserves that behavior.

The existing collection model represents manually selected membership. Existing
promo sections require a collection and support grid, row, and split layouts.
Automatic sources do not exist yet. Stage 2 will introduce explicit source rules;
Trending sales should not remain as a label unless real sales ranking is added.

## Stage 1 responsibilities

- `HomeController` retains the existing product query and passes its loaded result
  to `BroadSelectService::fromProducts`. Other homepage product sections remain
  unchanged.
- `BroadSelectService` prepares localized group metadata without querying or
  pretending that independent product sources already exist. It keeps both legacy
  choices backed by the same loaded sample during this extraction.
- `BroadSelectGroupData` defines each group's presentation contract:

  | Field | Meaning |
  | --- | --- |
  | `key` | Stable, unique identifier within a component instance. |
  | `label` | Resolved display text, escaped by Blade. |
  | `source` | Semantic product source; currently `review_count` or `top_rated`. |
  | `products` | Already loaded product models with required card relations/aggregates. |
  | `viewAllUrl` | Optional destination prepared by the caller; null when none is supplied. |

- `x-broad-select` receives groups, an optional heading/introduction, and wrapper
  attributes. It performs no queries and delegates choices/products to short
  partials. Empty groups omit the component. The current adapter renders the
  first group's sample and uses the existing browser sort; independent panels
  and View all rendering are deliberately deferred to later stages.
- Reuse `x-product-card-a` and the existing carousel hooks, speed of 60 CSS pixels
  per second, reduced-motion behavior, and reset event from the preceding fix.
- `storefront_ux.js` scopes selection and sorting to each `data-broad-select`
  instance. Removed the old global `horizontal`/`vertical` IDs, allowing multiple
  instances without duplicate IDs or cross-component selection.
- No new sources, eligibility rules, migrations, routes, admin forms, dependencies,
  or visual redesign are added in this stage.

### Stage 1 changed files

- `app/Data/Promotions/BroadSelectGroupData.php` (new)
- `app/Services/Promotions/BroadSelectService.php` (new)
- `app/View/HomeController.php`
- `resources/views/homepage.blade.php`
- `resources/views/components/broad-select.blade.php` (new)
- `resources/views/components/broad-select/choices.blade.php` (new)
- `resources/views/components/broad-select/products.blade.php` (new)
- `resources/js/components/storefront_ux.js`
- `tests/Feature/Promotions/BroadSelectComponentTest.php` (new)
- `tests/Feature/HomepageTest.php`
- `docs/BROAD_SELECT_PROMOS_PLAN.md` (new)

### Verification

- Standalone component tests cover escaped content, custom product keys/card hooks,
  rendering without queries, data-driven choice counts, repeated component markup,
  empty groups, and localized labels.
- Component and homepage/promo/localization checks: 15 tests passed, 93 assertions
  across the targeted runs, verifying existing page composition.
- All seven frontend test files pass, including the preceding carousel regressions.
- Browser fixture uses the extracted component and synthetic products without
  touching development data. Two instances retain independent selected choices
  and sorted rows. At 390px there is no horizontal document overflow.
- Vite production build, Pint, and whitespace checks pass.
- No Git commit or push is performed at this review gate.

## Stage 2: independent product sources

### Source contract and eligibility

`PromoSource` provides the allowed values `collection`, `top_rated`, and
`new_arrivals`. Persisted source strings remain tolerant of invalid historical
values: readers exclude unsupported sources instead of trying to render them.

- Collection sources require a published, nonempty collection and retain pivot
  order, then product_id as a deterministic tie-break.
- Top rated requires at least three reviews by default, configurable through
  `promotions.broad_select.minimum_reviews`. Order is average rating descending,
  review count descending, then product_id descending.
- New arrivals uses product created_at descending, then product_id descending.
  This is creation time, not an invented approval timestamp.
- All sources use the existing collection visibility rules: approved and active
  products, an active/approved vendor, and positive stock for promo presentation.
- Automatic sources do not reference a collection. Malformed associations,
  unsupported sources/layouts, inactive sections, and out-of-window schedules
  are excluded. Empty sources are excluded before applying the group limit.

The broad-select reader recognizes `homepage_broad_select`, with at most six
groups and twelve products per group by default. This placement is intentionally
not exposed in the admin placement dropdown until Stage 3. Automatic sources are
not enabled for existing `homepage_main` layouts in this stage.

### Architecture

- `ProductCatalogQuery` owns automatic source eligibility, ordering, eager card
  relations, review aggregates, and limits. Its eligibility builders are also used
  in section existence checks so inclusion and actual product loading agree.
- `PromoSectionQuery` retains publication/scheduling/order handling and batch
  collection loading. It loads each distinct automatic source once, even when
  several sections reuse it, and slices prepared products for each section.
- `BroadSelectService::forHomepage` maps prepared sections into the Stage 1 group
  DTO. Group keys use promo_section_id. It does not change or replace the legacy
  `fromProducts` adapter used by HomeController yet; panel integration is Stage 4.
- `PromoSectionData` safely prepares headings, introductions, and collection URLs
  for sections without a collection. Automatic sources currently have no View all
  URL: no route with different eligibility/ranking is substituted misleadingly.
- Shared promo rendering and the existing admin list handle absent collections.
  No new editor controls were added. The current editor continues saving
  collection-backed sections explicitly; source selection and automatic preview
  administration follow in Stage 3.

### Migration

`2026_11_21_000001_add_product_source_to_promo_sections_table.php` adds source with
a default of collection and makes product_collection_id nullable, retaining its
foreign key. Existing rows keep their collection associations and receive the
collection source default. The original create-table migration is unchanged.

Rollback refuses to restore the non-null constraint while collectionless sections
exist; it does not delete sections or invent memberships. Assign collections or
remove those sections intentionally before rolling back.

The new migration was applied successfully to the development database using its
specific path. No other migration was pending, and no reseeding was performed.
The implementation created no new development promo groups.

### Stage 2 changed files

- `app/Enums/PromoSource.php` (new)
- `database/migrations/2026_11_21_000001_add_product_source_to_promo_sections_table.php` (new)
- `config/promotions.php`
- `app/Models/PromoSection.php`
- `app/Queries/Products/ProductCatalogQuery.php`
- `app/Queries/Promotions/PromoSectionQuery.php`
- `app/Data/Promotions/PromoSectionData.php`
- `app/Services/Promotions/BroadSelectService.php`
- `app/Services/Promotions/PromoSectionManagementService.php`
- `resources/views/components/promo-section.blade.php`
- `resources/views/components/promo-layouts/heading.blade.php`
- `resources/views/admin/sections/index.blade.php`
- `lang/en/promotions.php`
- `lang/vi/promotions.php`
- `tests/Feature/Promotions/BroadSelectSourceTest.php` (new)
- `docs/BROAD_SELECT_PROMOS_PLAN.md`

### Stage 2 verification

- Eight new source tests pass: minimum reviews/ranking, stable creation ordering,
  bounded eager loading, shared visibility, collection publication/order,
  exact scheduling, empty-source exclusion before limits, malformed associations,
  source batching, translated metadata, and collectionless admin listing.
- Existing collection/query/performance checks: 20 passed, 86 assertions.
- Full suite run directly through PHPUnit: 525 passed, 7,017 assertions on
  larpshop_testing. The initial artisan wrapper run exited with PHP signal 11;
  direct PHPUnit completed successfully. No cause is assumed for that process exit.
- All seven frontend test files pass. Production Vite build, Pint, and whitespace
  checks pass. No frontend behavior, dependency, Git commit, or push was added in
  Stage 2. Homepage continues using the reviewed Stage 1 presentation.

## Stage 3: existing promo administration

### Editor and source validation

- The existing placement dropdown now exposes `homepage_broad_select`. The editor
  supports collection, Top rated, and New arrivals sources without needing a
  collection to exist first.
- Broad-select groups use the row layout. Main homepage sections remain
  collection-backed and retain all existing layouts. Changing placement updates
  available source/layout options; a helper explains automatic source availability.
- A shared Alpine `promoSourceForm` controls the editor and preview form. It
  restores saved/old state, fixes broad-select layout to row, resets sources to
  collection when leaving that placement, and controls collection visibility,
  required state, and disabled state. It does not own server-side eligibility.
- Save and preview requests share `ValidatesPromoSource`: only allowed enum values
  are accepted, automatic sources cannot carry a collection identifier, collection
  saves require an existing collection, and incompatible placement/layout/source
  combinations are rejected server-side.
- The management service saves explicit source metadata and associates or clears
  the collection foreign key inside its existing transaction. Collection membership
  is never deleted during a source switch. Activation also checks source integrity.
- Existing scheduling, toggle, ordering, and delete actions are reused. Moving a
  section into a full placement now respects the same management cap as creation;
  updating a section within its current placement remains allowed.

### Preview

- The existing read-only preview accepts a source and placement in addition to
  collection/layout. It can preview automatic sources with no collections present.
- Existing section defaults are resolved once through the query layer during
  request preparation and reused by the controller. Unknown identifiers and
  incompatible source settings still fail validation.
- `PromoSectionQuery::previewAutomatic` prepares an in-memory clone using public
  product eligibility while bypassing section activation/scheduling only for the
  authorized admin preview. Switching preview source never writes to the record.
- Collection previews still support drafts and the existing layouts. All previews
  reuse the inert product-card presentation and existing viewport controls.
- Preview controls and editor source fields are separate short partials. Long
  attributes remain split across lines; new UI copy is localized in English/Vietnamese.

### Stage 3 changed files

- `config/promotions.php`
- `app/Http/Requests/Promotions/ValidatesPromoSource.php` (new)
- `app/Http/Requests/Promotions/SavePromoSectionRequest.php`
- `app/Http/Requests/Promotions/PreviewPromoSectionRequest.php`
- `app/Http/Controllers/Admin/PromoSectionPreviewController.php`
- `app/Queries/Promotions/PromoSectionQuery.php`
- `app/Services/Promotions/PromoSectionManagementService.php`
- `resources/js/app.js`
- `resources/js/components/promo_source_form.js` (new)
- `resources/views/admin/sections/edit.blade.php`
- `resources/views/admin/sections/partials/form.blade.php`
- `resources/views/admin/sections/partials/fields.blade.php`
- `resources/views/admin/sections/partials/source-fields.blade.php` (new)
- `resources/views/admin/sections/partials/image.blade.php`
- `resources/views/admin/promotions/preview.blade.php`
- `resources/views/admin/promotions/partials/preview-controls.blade.php` (new)
- `lang/en/promotions.php`
- `lang/vi/promotions.php`
- `tests/Feature/Promotions/AdminBroadSelectTest.php` (new)
- `tests/Frontend/promo_source_form.test.mjs` (new)
- `docs/BROAD_SELECT_PROMOS_PLAN.md`

### Stage 3 verification

- Eight new admin tests pass: editor without collections, automatic save/schedule,
  switching collection associations, invalid combinations/payloads, move/toggle/cap
  behavior, preview without writes, unsaved/empty preview, and authorization.
- Existing management/preview/image/source checks: 30 passed, 208 assertions.
- Full PHPUnit suite: 533 passed, 7,104 assertions on larpshop_testing.
- All eight frontend test files pass, including shared form state coverage.
  Vite production build, Pint, and whitespace checks pass.
- Browser review used actual admin response HTML from isolated test fixtures:
  changing placement/source correctly toggles collection required/disabled/visible
  states, leaving broad-select restores collection source, editing restores saved
  automatic metadata, and automatic preview renders products without collections.
- No development records, migrations, dependencies, Git commits, or pushes were
  added in this stage. Public homepage panel switching remains Stage 4.


## Stage 4: independent homepage panels

- HomeController now calls BroadSelectService::forHomepage. The temporary
  fromProducts adapter and client-side review/rating sorting have been removed.
  The separate general product grid and main promo sections are unchanged.
- Each configured group renders its own preloaded product panel. Membership,
  ranking, visibility, scheduling, and the six-group/twelve-product bounds remain
  owned by the existing query layer. No AJAX endpoint or additional public query
  rules were introduced. With no eligible configured groups, the component is omitted.
- Every component instance generates unique panel identifiers. Selector buttons
  reference their panel through aria-controls and retain aria-pressed state.
  Inactive panels use hidden and are removed from layout and focus navigation.
- A small broad_select.js module switches panels without sorting, replacing cards,
  resetting rows, or rebuilding cart/wishlist controls. Repeated active clicks do
  nothing. Each component instance operates independently.
- CarouselScrollable recognizes initially hidden panels and carousel:visibility
  events. Hidden panels cancel animation frames and reset timing/velocity; showing
  a panel remeasures geometry and refreshes hover/focus state before resuming.
  Resize observers cannot restart an inactive panel. Existing row state is retained
  subject to normal browser scroll clamping when viewport dimensions change.
- Collection panels expose their prepared View all link. Automatic sources omit
  this link because no matching public listing route exists yet.

### Stage 4 changed files

- app/View/HomeController.php
- app/Services/Promotions/BroadSelectService.php
- resources/views/components/broad-select.blade.php
- resources/views/components/broad-select/choices.blade.php
- resources/views/components/broad-select/products.blade.php
- resources/css/broad_select.css
- resources/js/components/broad_select.js (new)
- resources/js/components/storefront_ux.js
- resources/js/components/carousel_scrollable_controller.js
- tests/Feature/HomepageTest.php
- tests/Feature/CatalogLocalizationTest.php
- tests/Feature/Promotions/BroadSelectComponentTest.php
- tests/Feature/Promotions/BroadSelectSourceTest.php
- tests/Frontend/broad_select.test.mjs (new)
- tests/Frontend/carousel_scrollable.test.mjs
- docs/BROAD_SELECT_PROMOS_PLAN.md

### Stage 4 verification

- Homepage integration covers distinct collection/rated/new product membership,
  server ordering, section scheduling, panel visibility, and collection links.
  Component tests check unique controls across instances and rendering without queries.
- Frontend tests cover repeated panel switches, component isolation, inactive
  carousel frames, visibility resume without catch-up, and fresh geometry after resize.
- All nine frontend test files pass. Thirty related PHP tests pass, 210 assertions.
- Vite production build, Pint, and whitespace checks pass.
- Browser review used a rendered homepage from isolated test fixtures. Switching
  groups before/after desktop/mobile resize shows only the requested panel; inactive
  panels have no layout width. No development records were created.
- The first full-suite run found one localization fixture relying on the removed
  default groups; it now explicitly creates a configured source. The homepage image
  fallback assertion now checks the existing grid's SVG rather than the removed
  default carousel's JPG. Two accidentally overlapping test processes collided on
  test-schema setup; subsequent database runs were performed sequentially.
- Final full PHPUnit suite: 534 passed, 7,119 assertions on larpshop_testing.
- No migrations, dependencies, commits, or pushes were added in Stage 4.
  Stage 5 remains a separate review gate for visual/mobile/accessibility polish.


## Stage 5: presentation and interaction polish

- Broad-select now uses a bounded desktop grid with a scrollable left column and
  independently scrollable products. Mobile choices form a start-aligned horizontal
  strip, so the first and last choices remain reachable even with six long labels.
  Product rows expose a thin native scrollbar and keyboard focus for manual scrolling.
- Selector colors use Bootstrap body/background/border tokens in both themes.
  Selection is a restrained contrasting surface, with small corners and visible
  focus outlines. Styling remains scoped to broad-select.
- Choices use tablist/tab/tabpanel semantics with unique labels and controls.
  One tab is in the tab order. Arrow keys follow the responsive orientation;
  Home/End move to the endpoints. Navigation moves focus without selecting;
  Enter/Space activate. Focused choices scroll into view without animation.
- A localized Pause automatic scrolling toggle stops all product rows within the
  component, including currently hidden panels. The preference survives group
  switches; resuming still respects reduced-motion, focus, hover, and page visibility.
  Reduced-motion also disables local decorative transitions.
- Broad-select product card controls stay visible in a compact footer in normal
  document flow, including on touch devices and keyboard focus. They do not cover
  the product price. Existing cart and wishlist routes, listeners, and shared state
  are unchanged.
- Public empty sources remain excluded by the query layer; a directly supplied
  empty product group also receives the existing localized no-products message.
  The section heading now uses h2 rather than introducing another page h1.

### Stage 5 changed files

- resources/views/components/broad-select.blade.php
- resources/views/components/broad-select/choices.blade.php
- resources/views/components/broad-select/products.blade.php
- resources/css/broad_select.css
- resources/js/components/broad_select.js
- resources/js/components/carousel_scrollable_controller.js
- lang/en/promotions.php
- lang/vi/promotions.php
- tests/Feature/Promotions/BroadSelectComponentTest.php
- tests/Feature/Promotions/BroadSelectSourceTest.php
- tests/Frontend/broad_select.test.mjs
- tests/Frontend/carousel_scrollable.test.mjs
- docs/BROAD_SELECT_PROMOS_PLAN.md

### Stage 5 verification

- Final full PHPUnit suite: 535 passed, 7,136 assertions on larpshop_testing.
  The focused broad-select/cart/wishlist run passed 42 tests, 278 assertions.
- All nine frontend test files pass. Added keyboard navigation/orientation/manual
  activation coverage, pause propagation to hidden panels, and persistent pause
  across panel visibility changes. Existing reduced-motion tests continue to pass.
- Integration coverage renders the same saved product in two groups, checks both
  wishlist removal forms and add-to-cart controls, and confirms a cart add succeeds.
- Browser review uses rendered test fixtures only: six groups with a long label,
  dark/light themes, desktop/390px mobile resize, End then Enter activation,
  pause state across group changes, and wishlist keyboard focus. The isolated
  component has no document-level horizontal overflow at either size.
- Vite production build, Pint, and whitespace checks pass. No development data,
  migrations, dependencies, Git commits, or pushes were added in Stage 5.

### Remaining scope boundaries

The automatic sources are Top rated and New arrivals, plus manually curated
published collections. No real sales ranking, automatic discounts, or additional
matching public listing route was invented. Automatic groups therefore still have
no View all link. Broader homepage/header layout issues are outside this component.


## Final review adjustments

- Restored the original uppercase selector typography, asymmetric corners, and
  active conic gradient while retaining responsive layout and keyboard behavior.
- Product-card-a wishlist/cart actions now share circular 44px transparent buttons
  with subtle borders, hover treatment, and visible keyboard focus.
- Selector scrollbars are hidden again while manual scrolling remains available.
- BroadSelectSeeder is called by DatabaseSeeder. It initializes New arrivals and
  up to five category-based published demo collections only when the placement
  has no configured groups. Empty categories are skipped; existing admin edits
  are preserved. It can be run separately with
  `php artisan db:seed --class=BroadSelectSeeder` without rebuilding the database.
- Final follow-up files: resources/css/broad_select.css,
  resources/css/components/product_card.css,
  resources/views/components/product-card-a.blade.php,
  database/seeders/BroadSelectSeeder.php, database/seeders/DatabaseSeeder.php,
  tests/Feature/Promotions/BroadSelectSeederTest.php.
