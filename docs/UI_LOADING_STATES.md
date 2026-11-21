# Loading states

`x-loading-state` is presentation only. Each request flow owns loading, success,
empty, and error behavior. Do not display a fake loading delay for preloaded data.

```blade
<x-loading-state
    :label="__('common.loading')"
    :hidden="true"
/>

<x-loading-state variant="skeleton" :hidden="true" />
```

The default is a Bootstrap spinner with visible text. Skeleton mode provides
three decorative placeholder lines; a custom slot can supply a shape that matches
its eventual content. Skeleton slots must contain decorative markup only.
Labels are translated and escaped; both variants expose a polite status message.
Motion stops when the browser requests reduced motion. Styles load through app.css.

Request code toggles the component's `hidden` property. It should also prevent
repeated submissions and mark the relevant trigger/content busy. Keep the status
outside any region marked busy so it can be announced during the request.
Always clear loading and restore usable controls after a failure, retain existing
content where possible, and provide a translated error and a retry action.

The first integration is product review pagination. Its delegated entry point is
product_reviews.js; review_pagination.js owns the request lifecycle. Previously
loaded reviews stay visible, repeated clicks are ignored, and the same Show more
reviews link retries failures. Its normal href remains available without JavaScript.

## Initial integration files

- resources/views/components/loading-state.blade.php
- resources/css/components/loading_state.css
- resources/css/app.css
- resources/js/components/review_pagination.js
- resources/js/components/product_reviews.js
- resources/views/products/partials/review-list.blade.php
- lang/en/common.php
- lang/vi/common.php
- tests/Feature/LoadingStateComponentTest.php
- tests/Feature/CatalogLocalizationTest.php
- tests/Frontend/review_pagination.test.mjs
- docs/UI_LOADING_STATES.md

## Product cards

`x-product-card-skeleton` provides decorative placeholders shaped like card A
(`variant="a"`) or the default catalog card B (`variant="b"`). It contains no
images, links, buttons, product data, or queries. Put it inside the shared loading
state so a whole list has one status announcement and inherits placeholder motion.

```blade
<x-loading-state variant="skeleton" :hidden="true" data-products-loading>
    <div class="row g-4">
        @for ($index = 0; $index < 4; $index++)
            <div class="col-6 md:col-3">
                <x-product-card-skeleton />
            </div>
        @endfor
    </div>
</x-loading-state>
```

For a horizontal carousel, use a flex row with gap and `variant="a"` instead.
The request owner toggles the loading container's hidden state and replaces it
with real cards on success, or an error/retry state on failure. Regular catalog lists are server-rendered. Homepage broad-select is now deferred
and uses these placeholders during its actual request.


## Deferred homepage broad-select

The initial homepage no longer queries broad-select groups. It loads the required
CSS in the head and renders x-broad-select.deferred, with a reserved loading area.
The main promo placement is also deferred as described below. Banners and the
general product grid remain unchanged.

homepage_promos.js starts DeferredSection using IntersectionObserver with a
300px prefetch margin. Browsers without it fetch immediately. A request runs once;
concurrent calls are ignored, and failures expose an explicit retry instead of an
automatic loop. A 15-second timeout prevents a permanently pending skeleton.

GET /home/broad-select (home.broad-select) invokes HomeBroadSelectController and
reuses BroadSelectService. The endpoint accepts no source, placement, or limit
options. It returns a Blade fragment or HTTP 204 when no groups are eligible.
The response is private/no-store because cards include CSRF and wishlist state.
Session locale and currency continue to apply to the request.

The fragment contains no styles or scripts. After insertion, the entry point
initializes selector behavior and carousels once. Existing quantity-picker
observation handles new cart controls; wishlist forms retain their normal routes.
A successful keyboard retry moves focus to the first selected tab. Empty results
hide the shell. Without JavaScript, the ordinary homepage grid and a catalog link
remain available instead of leaving a permanent skeleton.

Additional files:

- app/View/HomeController.php
- app/View/HomeBroadSelectController.php
- routes/web.php
- resources/views/homepage.blade.php
- resources/views/products/partials/home-broad-select.blade.php
- resources/views/components/broad-select.blade.php
- resources/views/components/broad-select/deferred.blade.php
- resources/css/broad_select.css
- resources/js/components/deferred_section.js
- resources/js/components/homepage_promos.js
- lang/en/promotions.php and lang/vi/promotions.php
- lang/en/common.php and lang/vi/common.php
- tests/Feature/HomepageTest.php
- tests/Feature/Promotions/BroadSelectSourceTest.php
- tests/Feature/Promotions/DeferredBroadSelectTest.php
- tests/Frontend/deferred_section.test.mjs

Verification: full PHPUnit suite passed 544 tests; all 11 frontend test files,
production Vite build, Pint, and whitespace checks passed. Browser review used
isolated test fixtures with a slow response and an intentional first-request 503:
no request before viewport entry, visible skeleton during retry, restored keyboard
focus, working selector/carousel after insertion, and no mobile document overflow.


## Main promo layouts

Grid, row, and split sections in homepage_main now load together through
GET /home/promo-sections (home.promo-sections). HomePromoSectionsController uses
the existing PromoSectionQuery with its existing publication, scheduling, visibility,
ordering, and bounded product/section limits. No placement/layout/limit supplied
by a client can expand this query. Empty results return 204; card responses are
private/no-store and use the current session locale, currency, and wishlist state.

Both homepage placements share x-deferred-section and DeferredSection. Each has
its own skeleton, pending/error/retry state, request timeout, and hydration call.
The main placement uses catalog-card skeletons; broad-select retains carousel-card
skeletons. Homepage CSS is loaded in the head. The new fragments contain no inline
asset tags; other pages and admin previews still load their existing assets normally.

The shared hydration entry initializes TwoArrowScroller for rows and restores
comparison checkboxes from the existing session selection. Global delegated compare
handlers, wishlist forms, and quantity-picker observation are reused. The main
fragment is a labelled focusable region for keyboard users returning from retry.

Additional main-placement files:

- app/View/HomePromoSectionsController.php
- resources/views/products/partials/home-promo-sections.blade.php
- resources/views/components/promo-sections-deferred.blade.php
- resources/views/components/deferred-section.blade.php
- resources/views/components/promo-section.blade.php
- resources/views/components/promo-layouts/row.blade.php
- resources/js/components/storefront_ux.js
- tests/Feature/Promotions/HomepagePromoSectionTest.php
- tests/Feature/Promotions/DeferredPromoSectionsTest.php
- tests/Frontend/product_comparison.test.mjs

Main-placement verification: full PHPUnit suite passed 547 tests (7,233 assertions),
all 12 frontend test files passed, and Vite/Pint/whitespace checks passed. Browser
review confirmed all three layouts after a delayed response, functioning row arrow
scrolling, compare selection synchronized across repeated cards and restored after
reload, and no document overflow at 390px. Test fixtures did not change development
data. Banners remain outside this change.

## Deferred similar products on product details

The primary product details and initial reviews remain server-rendered. Only the
similar-products section uses the shared DeferredSection lifecycle and card-B
skeletons. It starts loading within 300px of the viewport, with the same timeout,
localized error message, retry and keyboard focus handling as homepage sections.

ProductController no longer queries similar products. SimilarProductsController
checks the source product through the existing public detail query, then uses
ProductRetrievalService and ProductCatalogQuery to return at most six products
from the same category tree, excluding the current product. The fixed limit and
category are server-owned. Empty results render the existing empty message.
Responses are private/no-store and contain no asset tags. The endpoint uses an
additional path segment so it cannot shadow a product's canonical slug.

The product-page entry initializes the inserted row arrows and restores comparison
selection. Cart observation and wishlist forms reuse their existing behavior.

Files for this addition:

- app/View/ProductController.php
- app/View/SimilarProductsController.php
- routes/products.php
- resources/views/products/pages/show.blade.php
- resources/views/components/products/similar-deferred.blade.php
- resources/views/products/partials/similar-products.blade.php
- resources/js/components/similar_products.js
- lang/en/products.php
- lang/vi/products.php
- tests/Feature/ProductDetailTest.php

Focused verification passed 24 PHP tests (129 assertions), all 12 frontend test
files, the Vite build and Pint. Coverage checks that initial details do not query
similar products, category membership, current-product exclusion, empty results,
the fixed limit, localization, cache headers and hidden/missing source products.
The final full PHP suite passed 550 tests (7,262 assertions).


## Initial stylesheet placement

Render-blocking styles for the shared navigation and reusable components are now
registered with named `@pushOnce('styles', ...)` blocks. The app, admin and guest
layouts render that stack in their head. The 21 repeated navigation stylesheet
blocks have been removed from page bodies. The navigation component owns its
header/logo styles and the logo image has explicit intrinsic dimensions.

The category dropdown, broad selector, promo sections, row scroller, profile
image and image cropper register styles through the same stack. Row scroller and
cropper scripts use a separate scripts stack at the end of the layout. Script
registration identifiers differ from style identifiers because Blade once keys
are shared across stacks. Cropper CSS is an explicit head asset instead of a CSS
import attached to its body script. Deferred fragments still disable asset
registration and rely on styles provided by their host pages.

This keeps layout-affecting CSS available before the body is painted. It does not
hide the page until JavaScript runs. Product and responsive banner cards already
reserve their media aspect ratios; their image-loading behavior remains unchanged.
External fonts and images may still arrive progressively on slow connections.

Regression coverage in InitialStylesheetPlacementTest checks catalog, storefront
and admin preview responses using actual Vite asset tags, rather than disabling
Vite. It rejects body stylesheet links, checks component styles in the head, and
checks row scripts after the main content. Final verification: 553 PHP tests
(7,286 assertions), all 12 frontend test files, Vite build, Pint and whitespace
checks passed. A local browser check with CSS delayed by two seconds and scripts
by four seconds confirmed styled navigation while media continued loading; no
browser resource errors were recorded.

Files for this fix:

- resources/views/layouts/app.blade.php
- resources/views/layouts/admin.blade.php
- resources/views/layouts/guest.blade.php
- resources/views/components/nav-bar.blade.php
- resources/views/components/category-dropdown.blade.php
- resources/views/components/broad-select.blade.php
- resources/views/components/promo-section.blade.php
- resources/views/components/promo-layouts/row.blade.php
- resources/views/components/profile/profile-container.blade.php
- resources/views/components/image-cropper.blade.php
- resources/js/components/image_cropper.js
- tests/Feature/InitialStylesheetPlacementTest.php

The repeated body-level CSS blocks were removed from homepage; product index and
show; category index/show mockup; cart index; profile show; supports; vendor
storefront, reports and store settings; vendor category index/create/edit; vendor
product index/create/edit/images/variants; and vendor order index/show views.


## Homepage category discovery

The final homepage product section is now a category browser rather than a static
product grid. Its category links, heading and loading shell render with the page;
product queries begin when it approaches the viewport. Normal category URLs remain
available for no-JavaScript browsing and modified clicks/opening a new tab.

CategoryDiscovery owns category selection, expansion and request cancellation.
It uses the shared loading-state/card-skeleton components, a 15-second timeout,
localized empty/error/retry states and a reduced-motion-aware fade. A request ID
prevents stale responses or abort cleanup from replacing a newer selection.
Keyboard retry and expansion return focus to the rendered result region.

HomeDiscoveryController validates category IDs and the boolean expanded flag.
ProductCatalogQuery owns filtering, category descendants, stable newest-first
ordering, relation loading, ratings and fixed limits. Products must be approved,
active, in stock and belong to an active vendor. The collapsed query fetches at
most 13 records to determine whether to show the expand action, then renders 12;
expanded requests render at most 24. No client-provided limit is used. Responses
remain private/no-store because wishlist, cart and currency presentation depend
on the current visitor. Fragments do not contain asset tags.

The reusable product-grid component renders both real cards and matching skeletons.
It uses 2 columns by default, 3 at md, 4 at lg and 6 at 2xl (1536px). This gives
6x2 cards collapsed and 6x4 expanded on wide desktop screens. Smaller screens keep
the same item limits with fewer columns. The shared Grid helper now emits Bootstrap
6 prefix syntax and translates legacy xxl configuration to its 2xl equivalent.
The old forced light/complementary wrapper is replaced with semantic theme colors.
Existing promo layout configuration is unchanged; product-grid can also be used
by other sections without coupling their data sources to category discovery.

Files for this addition:

- app/View/HomeController.php
- app/View/HomeDiscoveryController.php
- app/Queries/Products/ProductCatalogQuery.php
- app/View/Components/Grid.php
- routes/web.php
- resources/views/homepage.blade.php
- resources/views/components/category-discovery.blade.php
- resources/views/components/product-grid.blade.php
- resources/views/products/partials/home-discovery.blade.php
- resources/css/components/category_discovery.css
- resources/js/components/category_discovery.js
- resources/js/components/homepage_promos.js
- lang/en/products.php
- lang/vi/products.php
- tests/Feature/HomepageTest.php
- tests/Feature/HomeDiscoveryTest.php
- tests/Frontend/category_discovery.test.mjs

Verification covers 12/24 limits, ordering, category descendants, ineligible items,
invalid inputs, translated customer actions, cache headers, loading, cancellation,
stale responses, expansion/collapse, retries, keyboard focus and reduced motion.
Browser review with disposable test fixtures confirmed 6x2 and 6x4 rows at 1920px,
2 columns without horizontal overflow at 390px, visible skeletons during category
changes, empty results, retry recovery and preserved comparison selection after
cards are replaced. No development records were created or changed.

Final category-discovery verification: 559 PHP tests (7,352 assertions), all 13
frontend test files, the production Vite build, Pint and whitespace checks passed.
