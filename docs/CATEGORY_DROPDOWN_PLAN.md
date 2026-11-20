# Category dropdown overhaul

Status: Stages 1-3 reviewed; Stage 4 implemented and verified for review.

## Approved direction

- Retain the custom dropdown portal, Floating UI positioning, and Bootstrap 6
  theme variables introduced by the preceding fixes on `fix/category-dropdown`.
- Desktop mega menu: roughly three quarters of the width for category groups and
  one quarter for a preview of the category under pointer or keyboard focus.
- Each parent category heads a list of direct children. Pack groups down the
  available height, then continue into additional columns. Scroll horizontally
  only after the columns exceed the available width. Avoid splitting a group
  across columns where possible. Oversized groups need their own overflow policy.
- Reuse `x-two-arrow-scroller` for explicit horizontal navigation. Retain touch
  and keyboard navigation rather than making pointer buttons the only access.
- Defer preview images and any slideshow until their data source and behavior
  have been agreed. No image fields, carousel, or new dependency in Stage 1.

## Stages and review gates

1. Extract the category dropdown component and prepare parent/child groups outside
   Blade. Preserve the current appearance, links, ordering, portal hooks, and flat
   fallback when there are no root categories.
2. Build the wrapped group/column layout, viewport bounds, horizontal controls,
   and narrow-screen layout. Reserve or hide the preview area as appropriate.
3. Add the preview panel and synchronize it with pointer hover and keyboard focus.
   Category links keep their navigation behavior. Add an explicit, separate search
   scope action; selecting a scope does not submit or navigate immediately.
4. Agree on and implement image presentation, then complete responsiveness,
   keyboard/reduced-motion behavior, sticky positioning, and integration checks.

Stop after each stage for review before continuing.

## Stage 1 responsibilities

- Existing controllers/composers continue supplying loaded navigation categories.
  This extraction does not change their filtering or ordering and adds no query.
- `App\View\Components\CategoryDropdown` receives that iterable and prepares each
  root model alongside its direct children, using the custom `category_id` key.
  Existing localized name/description accessors remain available on those models.
- `components/category-dropdown.blade.php` renders the trigger and menu template.
  `components/nav-bar.blade.php` invokes it with the existing category data.
- The JavaScript portal still owns open/close, hover, and automatic positioning.
  No interaction changes or mega-menu styling are introduced by this extraction.
- Existing behavior supports roots and direct children. Deeper descendants and
  children whose parent is absent retain their existing treatment; recursive
  navigation is not silently introduced.

### Stage 1 changed files

- `app/View/Components/CategoryDropdown.php` (new)
- `resources/views/components/category-dropdown.blade.php` (new)
- `resources/views/components/nav-bar.blade.php`
- `tests/Feature/CategoryDropdownTest.php` (new)
- `docs/CATEGORY_DROPDOWN_PLAN.md` (new)

The preceding theme and sticky-position fixes remain in
`resources/css/components/dropdown.css` and
`resources/js/components/dropdown.js`; Stage 1 does not modify those files further.


Stage 1 verification: 12 targeted tests passed with 34 assertions (new component
coverage plus homepage and category browsing). Coverage checks custom-key grouping,
input order, navigation links, escaped names, rootless/empty data, translated
trigger labels, and zero database queries during standalone component rendering.
Pint and whitespace checks passed. No database migration, dependency, or Git commit
was added in this stage.


## Search scope decision before Stage 2

The menu continues to provide category navigation. A later explicit action in the
preview will select a category as the search scope; clicking a category name will
still navigate. Scope selection should close the menu, focus the search input,
update its category label, and allow resetting to All categories. On narrow screens
that action must be reachable without the preview panel.

The existing `ProductSearchController` already validates `q` and `category`, and
`ProductCatalogQuery::searchProducts` combines both filters. Category accepts a
slug or numeric key and includes descendant products. Unknown categories return
no results. No search controller, query, or form submission behavior changed in
Stage 2.

## Stage 2 implementation

- The desktop category area uses CSS multi-column flow with `column-fill: auto`:
  groups fill the available height, then continue into columns to the right.
  `break-inside: avoid` keeps a normal group together. A group exceeding the
  list height has its own vertical scrolling area and can receive keyboard focus.
- The outer menu fills the available viewport width without horizontal margins
  or a border. Its inner Bootstrap container constrains and centers the content. Floating
  UI's size middleware supplies available space in addition to existing flip,
  shift, scroll, resize, and sticky-header positioning updates.
- Below 48rem, groups form a single vertically scrollable list; arrow controls
  are hidden. From 48rem, columns use a bounded list height (at most 30rem), with
  horizontal overflow and explicit arrow controls. From 64rem, the layout reserves
  a three-to-one split between navigation and a static preview area. Dynamic
  category previews, images, and search scope selection remain Stage 3/4 work.
- Reuse `x-two-arrow-scroller` with wrapper attributes and its existing overlay
  controls on opposite edges. Category-specific styles center the buttons
  vertically and leave room beside the lists so links remain unobstructed. Control targets are unique per component instance. Native touch
  and keyboard scrolling remain usable; scrollbars are hidden for the outer row.
- DropdownPortal initializes scrollers after cloning the template into the live
  portal. The existing page-load scan cannot reach inert template content.
  This does not add document-wide mutation observers or per-menu global listeners.
- Closing clears hover timers and positioning observers. A removal fallback also
  cleans up menus closed before a transition begins; reduced-motion users receive
  immediate removal. This prevents hidden menu nodes accumulating in the portal.
- Menu/group rendering is split into short Blade partials. New labels and the
  empty state have English/Vietnamese translations. Theme colors use Bootstrap
  variables, with hover/focus states and rounded corners no larger than rounded-2.

### Stage 2 changed files

- `app/View/Components/CategoryDropdown.php`
- `resources/views/components/category-dropdown.blade.php`
- `resources/views/components/category-dropdown/menu.blade.php` (new)
- `resources/views/components/category-dropdown/group.blade.php` (new)
- `resources/views/components/two-arrow-scroller.blade.php`
- `resources/css/components/two_arrow_scroller.css`
- `resources/css/components/dropdown.css`
- `resources/js/components/dropdown.js`
- `lang/en/category_menu.php` (new)
- `lang/vi/category_menu.php` (new)
- `tests/Feature/CategoryDropdownTest.php`
- `docs/CATEGORY_DROPDOWN_PLAN.md`

### Stage 2 verification

- Targeted category/homepage/search/promo-markup tests: 27 passed, 110 assertions.
- Full application suite: 508 passed, 6,910 assertions.
- All four frontend test files, Vite production build, Pint, and whitespace checks
  passed. No database migration, new dependency, or Git commit was added.
- Browser review used synthetic category models in temporary HTML, without
  modifying development data. A 16-group fixture included a group with 30 children.
  At 1440x900, normal groups packed vertically into columns; the oversized group
  scrolled independently and both pointer/keyboard arrows moved the outer list.
  At 390x844, the preview/arrows were hidden and all links remained in a vertically
  scrollable list. The menu stayed inside the viewport and retained a zero gap
  below its trigger after the header became sticky. At 1100x400, the menu bottom
  remained within the viewport and the column height shrank to available space.
- This is a targeted layout review; final preview interaction, image behavior,
  and broader accessibility/browser review remain in the later stages.


### Stage 2 width refinement

The outer dropdown surface is now edge-to-edge, with no border or rounded edge.
Floating UI uses zero viewport padding, so its width/position agree with that
surface. The content uses Bootstrap's responsive `container`, preserving the
75/25 navigation/preview ratio at desktop widths.

Navbar and persistent-header surfaces also span the page width. A narrowly scoped
`header.container:has(> .navigation-bar)` rule removes the old outer container cap
and horizontal padding on existing shared-navbar wrappers. The navbar's inner
wrapper and a persistent-header inner wrapper each use a Bootstrap container.
This keeps all three content areas aligned without rewriting every page's header
or changing the sticky header's parent/primary-nav structure. Existing background
colors and persistent-header scrolling logic are preserved.

Additional files changed for this refinement:

- `resources/css/components/navigation_bar.css`
- `resources/views/components/nav-bar.blade.php`
- `resources/views/components/persistent-header.blade.php`

Verification with the real navbar/persistent-header components at a 1920px
viewport (1905px excluding the scrollbar): outer header, navbar, and dropdown all
span 1905px from x=0; their inner content containers are 1440px wide and centered.
The dropdown columns measure 1044px and 348px, retaining the 3:1 ratio. Border
width is zero. Full suite remains green: 508 tests and 6,910 assertions; all four
frontend test files and the Vite production build pass.

### Stage 2 arrow placement refinement

Category controls now overlay opposite edges of the navigation area, centered
vertically. Removed the unused footer-control variant from the shared component.
Mobile controls remain hidden. Smooth scrolling uses the existing shared
`scrollBy({ behavior: "smooth" })` implementation; reduced-motion preferences
select immediate scrolling instead.

## Stage 3 implementation

- Preview text follows pointer hover and keyboard focus for both parent and child
  categories. It uses existing localized names/descriptions, with a translated
  fallback when no description exists. The initial preview uses the selected
  search category, or the first entry. Images remain Stage 4 work.
- Category links still navigate. A separate search icon beside every category
  selects search scope on desktop and mobile. The preview also exposes Browse
  category and Search in this category actions. All categories clears scope.
- Selecting scope updates the search form's hidden `category` input and trigger
  label, closes the portal, and focuses `q`. It does not submit or erase the
  keyword. Scope is restored from a valid slug or custom primary key on render;
  unknown/array values cannot become active scope. A cleared filter is disabled
  and omitted from the GET request.
- `CategoryMenu` owns preview/scope behavior. `DropdownPortal` remains responsible
  for visibility, positioning, and keyboard entry/exit. Each trigger refers to
  its own template, avoiding accidental coupling between component instances.
- ArrowDown enters the list, Escape returns focus to the trigger, and moving
  focus outside closes the portal. Pointer leave does not close it while focus
  remains inside. Fading-out menus are inert and hidden from accessibility tools.
- Blade escapes category metadata; JavaScript writes preview content through
  textContent. No HTML injection, database queries, schema changes, or dependencies
  were added. Existing search validation and queries remain authoritative.

### Stage 3 changed files

- `app/View/Components/CategoryDropdown.php`
- `resources/views/components/category-dropdown.blade.php`
- `resources/views/components/category-dropdown/menu.blade.php`
- `resources/views/components/category-dropdown/group.blade.php`
- `resources/views/components/category-dropdown/item.blade.php` (new)
- `resources/views/components/category-dropdown/preview.blade.php` (new)
- `resources/js/components/category_menu.js` (new)
- `resources/js/components/dropdown.js`
- `resources/css/components/dropdown.css`
- `lang/en/category_menu.php`
- `lang/vi/category_menu.php`
- `tests/Feature/CategoryDropdownTest.php`
- `tests/Frontend/category_menu.test.mjs` (new)
- `docs/CATEGORY_DROPDOWN_PLAN.md`

### Stage 3 verification

- Full PHP suite: 510 tests passed, 6,937 assertions.
- All five frontend test files pass, including preview/scope interaction coverage.
- Vite production build, Pint, and whitespace checks pass.
- Browser checks with synthetic data: keyboard preview updates; selecting scope
  closes the menu and focuses search without navigation; resetting scope preserves
  the keyword; mobile search icons work with the preview hidden; submitting sends
  both `q` and `category` to `/products`. Reopening restores the selected scope.
- No development records were modified. No Git commit was created.

## Stage 4 implementation

### Image selection and responsibility boundaries

Category has no dedicated image field. Optional curated images are configured in
`config/category-previews.php`, keyed by slug. A path points into the public
storage disk; absolute HTTP(S) URLs also work. No schema or upload/admin changes
are needed for this stage. Example:

```php
'images' => [
    'helmets' => ['categories/helmets.webp'],
],
```

For each active category, prefer its configured images. Otherwise use up to three
images from approved, active products directly in that category. If neither
source exists, inherit images recursively from its descendants, in navigation
order. Deduplicate URLs and retain at most three. An empty subtree uses the
existing product placeholder; images never come from unrelated branches.
Curated images on descendants can also be inherited. Cycles terminate safely.

`CategoryPreviewQuery` retrieves bounded product-image samples for all persisted
navigation categories in a single query, using a window rank per category. It
uses existing catalog visibility scopes and custom primary keys; it does not
load every product or image or query once per menu item. Categories supplied as
unpersisted fixtures require no database access.

`CategoryPreviewService` owns image priority, URL presentation, and inheritance.
A dedicated `CategoryDropdownComposer` supplies the resulting image map only to
the dropdown view. The class-based component retains the complete loaded category
collection, including deeper descendants needed for preview inheritance; its
navigation markup continues showing roots and direct children.

### Frontend behavior

- Image metadata is escaped in Blade and read alongside existing preview data.
- `CategoryPreview` owns slide content and Bootstrap instance lifetime;
  `CategoryMenu` continues owning category selection/text. The portal disposes
  image behavior on close. Fading menus relinquish IDs so rapidly reopening cannot
  send Bootstrap controls to an old carousel.
- One image is static. Multiple images use the installed Bootstrap 6 carousel
  API (`autoplay`, `ends`) with a five-second interval, previous/next controls,
  and a play/pause button. Pointer hover and keyboard focus pause movement;
  reduced-motion preferences disable automatic playback.
- Category changes dispose the old slideshow before rendering the new one.
  Closing the menu removes observers/timers via Bootstrap disposal and removes
  media-query listeners. Mobile creates no image nodes while the preview is hidden;
  crossing the breakpoint rerenders or disposes as appropriate.
- A fixed 4:3 image area uses object-fit contain and rounded-2. Broken image loads
  fall back once to the existing placeholder. Preview overflow scrolls on short
  screens; the menu remains bounded by Floating UI's available space.

### Stage 4 changed files

- `config/category-previews.php` (new)
- `app/Queries/Categories/CategoryPreviewQuery.php` (new)
- `app/Services/Categories/CategoryPreviewService.php` (new)
- `app/View/Composers/CategoryDropdownComposer.php` (new)
- `app/View/Components/CategoryDropdown.php`
- `app/Providers/AppServiceProvider.php`
- `resources/views/components/category-dropdown/item.blade.php`
- `resources/views/components/category-dropdown/preview.blade.php`
- `resources/views/components/category-dropdown/preview-images.blade.php` (new)
- `resources/js/components/category_preview.js` (new)
- `resources/js/components/category_menu.js`
- `resources/js/components/dropdown.js`
- `resources/css/components/dropdown.css`
- `lang/en/category_menu.php`
- `lang/vi/category_menu.php`
- `tests/Feature/CategoryPreviewTest.php` (new)
- `tests/Frontend/category_preview.test.mjs` (new)
- `docs/CATEGORY_DROPDOWN_PLAN.md`

### Stage 4 verification

- Full PHP suite: 514 passed, 6,961 assertions.
- All six frontend test files pass. Coverage includes carousel disposal, breakpoint
  changes, reduced motion, placeholder handling, and existing category/search scope.
- Vite production build, Pint, and whitespace checks pass.
- Backend tests verify descendant inheritance, curated priority, per-category
  limits, deduplication, cycle handling, visibility filtering, and one-query loading.
- Browser review with synthetic category data and existing local image assets
  confirms inherited root images, working next/play controls, switching to a
  single-image category, empty-category placeholders, Escape/focus return, and
  mobile removing image nodes. At 1100x400 the menu stays within the viewport and
  the preview scrolls; at 390x844 there is no horizontal page overflow.
- No development data, schema, dependencies, or Git commits were changed.
