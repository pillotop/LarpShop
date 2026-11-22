# Reusable Banner Groups

## Stage 1: Data contract

Platform banner groups are separate from the existing vendor-owned `Banner` records.
Vendor ownership, authorization, upload handling, cache observers, and `vendor_header`
remain unchanged. Public pages and admin management are connected in stages 3 and 4 below.

- `BannerGroup` (`banner_group_id`): unique stable slug, internal English name,
  layout key, publication flag, and optional start/end dates.
- `BannerCard` (`banner_card_id`): belongs to one group. Contains English title,
  optional eyebrow/CTA/badge, required alternative text and destination, optional
  Vietnamese translations, presentation, text tone, focal coordinates, image metadata,
  active flag, and ordering. Set membership through `group->cards()`.
- `BannerGroupPlacement` (`banner_group_placement_id`): reusable group assignment
  to a named page placement with independent ordering. Each group may occur once
  in each placement. Currently only `homepage_main` is registered.
- `PromoSection::bannerGroup()` is an optional reusable group attachment, assigned
  through the relationship. Deleting a group clears the attachment without deleting
  the promotional section. Deleting a group cascades its cards and placements.
  Database deletion does not delete image files; file lifecycle handling belongs to
  the management service.

## Registered layout contracts

| Key | Active cards | Ordered slots |
| --- | --- | --- |
| `duo` | 2 | Left, right |
| `trio` | 3 | Left, middle, right |
| `feature` | 3 | Large left, upper right, lower right |
| `mosaic_4` | 4 | Large left, upper right spanning two columns, lower middle, lower right |
| `mosaic_5` | 5 | Large left, upper middle, lower middle, upper right, lower right |

Card order is ascending `sort_order`, with primary-key tie breaking for legacy/imported
records. Management input requires distinct sort positions. Templates and responsive
behavior are implemented by the shared group component; the registry does not accept
template paths from database records.

## Presentation and localization

- `presentation`: `top_copy` or `bottom_caption`.
- `text_tone`: `dark` or `light`, chosen for the image rather than the site theme.
- `focal_x` and `focal_y`: integers from 0 to 100, defaulting to the image center.
- English content is stored in base columns; `translations.vi` may override any
  text field. Missing or blank translations fall back to English via
  `BannerCard::localizedText()`. Internal names, destinations, and image paths are
  not translated. Text is rendered through escaped Blade output.
- Destinations support root-relative paths and absolute HTTP(S) URLs. Protocol-relative
  links, credentials, backslashes, control characters, and executable schemes are rejected.
- Image dimensions, variants, disk, and path are trusted processing metadata. The shared
  content rules deliberately reject these fields in nested card management input. The
  upload service must validate the file and assign metadata separately in stage 3.

`BannerGroupRules::rules($data)` supplies validation for full content payloads. Database
uniqueness checks, upload validation, authorization, record IDs, and transactional
persistence are handled by the stage 3 management requests/service. Drafts may have
zero to five cards; active payloads require the exact layout count with all cards active.
Do not pass raw request arrays directly to model creation.

## Visibility

`BannerGroupQuery` supports placement reads and individual group reads for future
promo attachments. Reads are intentionally uncached; the integration policy is described below.
They eagerly load ordered active cards, exclude unknown layouts, and require an exact
active-card count. Incomplete groups are filtered in SQL before applying placement
limits. Groups use inclusive start and exclusive expiry boundaries. Explicit-time reads
are supported for deterministic previews/tests. Images and destinations must be validated
when saving; the visibility query does not perform file-system existence checks.

## Rollout

The migration is additive; it does not move existing banners, modify vendor ownership,
seed content, or replace homepage markup. Apply it with the normal migration command
before using banner groups. Rollback removes the new tables and the
optional promotion attachment. Do not run a destructive database reset.

All six stages are complete. Demo seeding and final verification are documented below.

## Stage 2: Rendering and sample preview

- `<x-banner-group :group="$group" />` renders all five registered layouts through
  `resources/css/components/banner_groups.css`. Pass `priority="true"` only to a
  group above the fold; only its first image is eager/high priority. Other images
  are lazy, have intrinsic dimensions, and use available width variants.
- `x-banners.card` is the internal tile renderer. It localizes text, escapes copy,
  clamps focal percentages, and uses the shared `BannerDestination` allowlist.
  Unsafe destinations become noninteractive articles. No nested links are emitted.
- `top_copy` puts the text at the top; `bottom_caption` places it at the bottom
  with a directional arrow. Optional badges stay at the top. Light/dark text and
  their local contrast gradients are independent of the application theme.
- Cards use `rounded-2`. Hover-capable devices get a small image zoom; reduced-motion
  preferences suppress animation. Keyboard focus has an inset outline.
- Container queries respond to the actual component width, including preview frames
  and future narrow placements. Under 36rem cards stack; between 36rem and 56rem
  supporting cards use two columns; at 56rem the complete desktop layouts apply.
  Trio's first card spans the tablet row to avoid leaving an isolated last tile.
- The renderer checks layout/count defensively, but intentionally does not enforce
  publication dates: future public integrations must use `BannerGroupQuery`, while
  authenticated previews may render drafts.

Admins can open `/admin/banner-groups/preview` from the sidebar. GET controls choose
layout, presentation, tone, and full/tablet/mobile width. Preview links are disabled,
content is synthetic, and no banner records or files are written. The page uses
existing repository images through the `public_assets` disk; this disk is only for
repository-owned assets and must never be selected by an upload request. Its image
URLs follow `APP_URL` like the existing public storage disk.

The preview page has a scoped mobile shell adjustment and a dashboard return link.
Other admin pages retain their current layout. It supports English and Vietnamese
through `lang/{en,vi}/banner_groups.php` and the existing language settings.

Persistent admin editing and uploads are implemented in stage 3. Stage 4 connects the
shared components to homepage and promotion rendering.

## Stage 3: Admin management

Admins manage groups at `/admin/banner-groups`. Create/edit forms submit the full group
and card list with CSRF protection. `SaveBannerGroupRequest` reuses the content contract,
checks slug uniqueness, scopes existing card IDs to the route group, rejects image metadata
in request data, validates files, and requires an image for each new card. Membership IDs
are not mass assignable. Deleting or modifying another group's card through a forged payload
is rejected. A draft can contain zero cards; publishing requires the layout's exact count.

`BannerGroupManagementService` locks the group, reconciles card membership and placement
assignments in a transaction, and retains existing image metadata unless a new file is supplied.
Uploaded files are cleaned up on rollback; replaced/removed images are cleaned up after commit.
Cleanup only removes generated paths in the dedicated `banner-groups/` directory on the upload
disk and skips referenced files. Shared `public_assets` files are never deleted. Direct model
updates bypass this lifecycle: management writes must use the service.

The editor supports adding/removing up to five cards, moving cards up/down, English/Vietnamese
copy, focal sliders, independent per-card presentation and text tone, publication schedules,
and placement order. Its live preview uses the shared card template and layout CSS. Language
and width controls affect only the preview; they do not change account preferences or saved
content. Layout changes do not silently discard cards. Removing a card takes effect on save.
Newly selected files must be selected again after a server validation error.

Uploads accept JPEG/PNG/WebP up to 8 MB, 12 million pixels, with both sides between 320 and
8192 pixels. `BannerCardImageProcessor` checks actual image headers before decoding, corrects
JPEG EXIF orientation when available, strips metadata, preserves aspect ratio, bounds the
longest edge to 1600 pixels, and writes WebP plus smaller responsive widths. It never uses
client-supplied paths or original filenames. Focal cropping remains a rendering concern,
so changing layouts does not require another upload.

The Docker runtime enables EXIF and sets `upload_max_filesize=8M`, `post_max_size=48M`, and
`memory_limit=256M` for a full five-image form. Rebuild the image for these PHP settings.
For a non-Docker local PHP server, configure equivalent limits or launch the server with
`php -d upload_max_filesize=8M -d post_max_size=48M -d memory_limit=256M artisan serve`.
No application logic can override PHP's upload limit after a request has already arrived.

Stage 3 does not add banner caches. Stage 4 supplies the public rendering hooks;
database seed content remains stage 5.

## Stage 4: Public homepage and promotion integration

`HomeController` resolves `homepage_main` groups through `BannerGroupQuery`. The former
hardcoded category-banner grid is replaced by ordered `<x-banner-group>` components in
`#main-banners`. If no eligible groups exist, that region is omitted without a blank gap
or a static fallback. No content is seeded in this stage: create and enable a complete
homepage-assigned group in admin, or use the stage 5 demo seeder when available.

The first image of the first visible homepage group is eager/high priority. All other
banner images are lazy. Banner CSS is included in the initial homepage head even when
there are no hero groups, so banners arriving in deferred product fragments are styled
immediately. Fragments do not inject stylesheets or scripts.

The promo-section editor has an optional banner-group selection, including older selected
records outside the recent choices. The full group is reused, not copied. Passing an empty
selection detaches it; older clients that omit the field retain the existing attachment.
IDs are validated server-side and associated through the model relationship, not mass input.
Deleting a group clears attached promo references through the existing foreign key.

Public promo data includes an optional prepared `bannerGroup`. `PromoSectionQuery` batches
all distinct attachment IDs and loads groups/cards in two extra queries, regardless of the
number of sections. With no attachments, there are no extra banner queries. Prepared views
do not issue lazy database reads. The existing eight-collection preparation budget grows
from at most six to at most eight queries when groups are attached.

The group appears between a promo heading and its product layout. Broad Select carries the
same group in its prepared DTO and renders it inside the corresponding tab panel, above its
product row. Tab switching, product actions, and existing deferred loading remain unchanged.
Attached groups do not need their own homepage placement assignment.

A single request cutoff is shared by sections and their attached groups. Group schedules,
publication flags, registered layouts, and exact active-card counts are always enforced.
An unavailable group hides only the banner: it does not suppress otherwise eligible products.
An unavailable product section does not become a standalone banner section. Admin promotion
previews use the same banner visibility rules; drafts can still be inspected in their own
banner-group editor without publishing them.

Banner-group reads intentionally remain uncached. Changes and schedule boundaries are checked
on every new request, with no stale application cache to invalidate. Both deferred endpoints
retain their existing `private, no-store` response policy. Already rendered pages do not
live-refresh when a schedule boundary passes.

Stage 5 demo assets/seeding and stage 6 final verification are implemented below.

## Stage 5: Bundled demo content

`BannerGroupSeeder` creates five complete English/Vietnamese sample groups:

| Slug | Layout | Initial use |
| --- | --- | --- |
| `demo-home-discoveries` | `mosaic_4` | Homepage, only if no homepage assignment already exists |
| `demo-care-duo` | `duo` | New Featured finds sample section |
| `demo-everyday-trio` | `trio` | New Broad Select New arrivals sample tab |
| `demo-creative-feature` | `feature` | Available for admin selection |
| `demo-discovery-mosaic` | `mosaic_5` | Available for admin selection |

All groups are enabled without time limits. Only the first group receives a standalone
placement; enabling a group alone does not place it on a page. The content is evergreen,
with no fabricated discounts, prices, brands, or deadlines. Destinations use active category
slugs when available at initial seeding, otherwise `/products`. The seed does not create
categories, products, collections, accounts, or payments. Missing catalog content does not
prevent the banner groups from being created.

Run only the additive banner seeder on an existing installation:

```sh
php artisan db:seed --class=BannerGroupSeeder
```

The normal `DatabaseSeeder` calls it before the existing promotional seeders. The latter
attach banner groups only when creating their sample sections for the first time. Existing
sections are deliberately untouched, including empty attachments: on an established database,
choose a group in the promotion editor if desired. Do not rerun the full DatabaseSeeder just
to add banners; unrelated legacy sample seeders can update other application data.

Each group's creation, cards, and initial placement are atomic. Existing slugs are skipped
entirely, preserving admin names, publication flags, schedules, card changes/deletions, and
removed placements. A homepage assignment already present, even for a disabled group, is
treated as administrator configuration and is not expanded. An incomplete existing group is
not silently repaired. Deleting an entire demo group allows the next run to recreate that slug.

### Asset provenance and delivery

These samples reuse five photographs already checked into the repository; no new external
images, generated product depictions, or third-party requests are introduced. Existing source
filenames identify the original creators; original licensing/provenance remains unchanged.
This stage does not claim independent verification of those licenses.

| Bundled subject | Existing source in `public/images/` |
| --- | --- |
| Care | `aestheticjourney-cream-1x1.jpg` |
| Tech | `17067741-camera-1x1.jpg` |
| Kitchen | `viarami-product-5222331_150.jpg` |
| Beauty | `8618939-makeup-150.jpg` |
| Fabric | `engin_akyurt-fabric-2405110_66.jpg` |

The 15 committed WebP files in `public/images/banners/` total approximately 760 KiB.
They retain the original aspect ratios, with the longest edge capped at 1600 pixels
and smaller 480px/960px-wide versions. The portrait beauty main image is 1067px wide.
Conversion uses EXIF orientation, strips metadata, and encodes at quality 85. Original
photographs and login carousel assets remain untouched. Copy and links are HTML rather
than baked into images. No image generation service or runtime conversion is required.

The seeder verifies each file's real dimensions and WebP header and stores responsive
metadata on the `public_assets` disk. Admin replacement uploads use the existing public
upload disk; replacing or deleting a seeded card never removes bundled files.

### Stage 5 verification

The banner and promotion suites pass **140 tests / 1132 assertions**, including six
seeding checks for complete layouts, real image dimensions, responsive EN/VI rendering,
repeat execution, administrator changes, existing homepage assignments, and promotion
attachment compatibility. Pint and whitespace checks pass. The dedicated banner seeder
was run on the development database and read back: five groups, 17 cards, and one eligible
`demo-home-discoveries` homepage assignment. No full database seeder, reset, migration,
external image request, or deployment was performed. Existing promo sections were left
unchanged; their optional attachments remain configurable in admin. Stage 6 final browser
and release verification is recorded below.

## Stage 6: Final verification and handoff

Final regression results on 22 November 2026:

- Entire PHP suite: **807 tests passed, 9807 assertions** on isolated MySQL `larpshop_testing`.
- All **15 frontend test files** passed with the Node test runner.
- Production Vite build, global Blade compilation, Pint for changed PHP files, and
  `git diff --check` passed.

Two legacy homepage tests still expected the retired static banner copy and category
links. Their fixtures now seed the real banner groups and assert Vietnamese copy and
the valid category-filter destination. The category-menu test fixture also lacked the
native `dispatchEvent` method now used to notify search suggestions of scope changes;
it now records and asserts the three bubbling change events, including scope reset.
Application behavior was not weakened to satisfy these tests.

### Browser checks

The actual application ran on `127.0.0.1:8776` against `larpshop_testing`, with dedicated
factory admin/customer accounts, sample catalog data, a separate session directory and
cookie, and the real seeded banner and promotion data. No authentication bypass or
mocked rendering routes were used.

| Check | Result |
| --- | --- |
| Homepage desktop | Mosaic 4 rendered four loaded responsive images; only the first was eager/high priority |
| Deferred content | Duo attached to Featured finds rendered; scrolling loaded the Broad Select trio; switching to a tab without an attachment hid only that tab's banner |
| Keyboard | Tab moved between banner destinations with a visible solid focus outline |
| Mobile homepage/editor | 390px viewport, document width 375px; stacked hero cards were 351px wide, with no horizontal overflow |
| Editor preview | Vietnamese text rendered; preview cards were noninteractive; changing content and moving the first card down updated the preview |
| Persistence | Saving preserved the edited title and order; the public homepage reflected both on the next request |
| Publication guard | Selecting Mosaic 5 with four cards produced a server validation error instead of publishing |
| Schedule | An expired hero disappeared without suppressing Featured finds; clearing expiry restored the hero |
| Five layouts | Duo, Trio, Feature, Mosaic 4 and Mosaic 5 rendered their expected desktop slots at a 1680px viewport without page overflow or copy-box overflow |
| Theme and locale | Dark homepage, light admin previews, and Vietnamese mobile preview were inspected; text tones remain image-specific |
| Access control | Guest admin navigation redirected to login; authenticated customer admin navigation returned 403 |
| Browser errors | No console errors were recorded in the checked journey |

Upload processing, rollback/cleanup, forged card IDs, unsafe links, escaped content,
schedule boundaries, attachment visibility, and bounded SQL preparation remain covered
by the passing feature suites. Reduced-motion behavior was reviewed in CSS: banner
image transitions and hover zoom are disabled for that preference. Separate browser
engines, screen readers, and operating-system motion settings were not certified.

Temporary browser/server resources were closed after verification. Browser edits and
fixtures were confined to the testing database; stage 6 did not change development or
Render data, seed production, run migrations, commit, merge, push, or deploy.

### Deployment checklist

1. Apply the additive banner migration with normal `php artisan migrate` if not already applied.
2. Build frontend assets with `npm run build`; include the committed `public/images/banners/`
   assets in the release. Rebuild the Docker image to pick up EXIF and upload-limit settings.
3. Optionally run only `php artisan db:seed --class=BannerGroupSeeder` to initialize sample
   groups. Existing homepage assignments and administrator edits are preserved. Production
   environments require the normal explicit Laravel seeding confirmation/flag.
4. Use admin to attach groups to existing promotions, since seeding intentionally does not
   rewrite existing promotion configuration. Review both image crops and text tones before
   enabling custom campaigns.
5. Check homepage and promotion fragments on the target environment. Uploaded banner files
   continue to use the application's existing public upload disk and storage URL setup.

No banner work remains in the agreed six-stage scope. Refunds and personalization are
separate future features, not dependencies of this release.

## Additional user-supplied artwork

`SuppliedBannerSeeder` imports the 11 JPEGs supplied on 22 November 2026 into four
reusable groups, with English/Vietnamese headlines, image descriptions and link labels:

| Slug | Layout | Artwork |
| --- | --- | --- |
| `supplied-everyday-upgrades` | Trio | Pastel headphones/mouse, controller/care accessories, neutral skincare |
| `supplied-colorful-discoveries` | Feature | Geometric color background, shopper on yellow, headphone listener on white |
| `supplied-seasonal-gifting` | Trio | Botanical skincare, festive makeup/perfume, chocolate gifts |
| `supplied-thoughtful-care` | Duo | Pink fragrance with silver ribbon, pink pump bottles with red bow |

The sources are the user attachments, including recycled full-background versions of
earlier login artwork. Original downloads and existing login assets remain unchanged.
No image generation, background removal, compositing or external download is performed.
The 33 bundled WebP files under `public/images/banners/supplied/` total approximately
1715 KiB, retain source aspect ratios, strip metadata, and provide 480px/960px variants.
Square originals stay at 1024px; larger landscape originals are bounded to 1600px.

Top copy uses the supplied empty upper space. Light backgrounds use dark text; the
dark teal chocolate scene uses light text. Portrait subjects use a 70% horizontal
focal point. These settings remain editable in admin. Generic/mixed-subject cards link
to `/products`; category-specific cards use an active category when present, otherwise
fall back to the catalog. No discount, stock availability, or brand claim is invented.

Run `php artisan db:seed --class=SuppliedBannerSeeder` to add only this artwork. The normal
DatabaseSeeder also includes it. Existing slugs are skipped completely on reruns. Groups
are enabled but unassigned, so importing them does not expand or replace existing homepage
content or promotions. Select them in admin as desired; seasonal imagery has no automatic
expiry until an administrator chooses a campaign schedule.

## Shared homepage ordering

Admins can open **Homepage layout** at `/admin/homepage-layout` from the sidebar,
Banner groups, or Promo sections. It lists banner groups assigned to `homepage_main`
and product sections using that placement, including disabled or currently invisible
records, plus the Broad Select carousel and category-discovery section. Assign newly
imported banner groups through their existing editor first. Unassigned reusable groups
and individual Broad Select tabs are not independent blocks in this list. Broad Select
and category discovery each move as one complete interactive section.

Give each row a distinct nonnegative number and save. Lower values appear first; saved
positions are normalized to consecutive ranks. For example:

```text
0  Everyday discoveries       Banner group
1  Featured finds            Product section
2  Pastel everyday upgrades  Banner group
3  More discoveries          Product section
```

The additive migration `2026_11_22_000002_add_shared_homepage_positions.php` adds nullable
`homepage_position` fields to banner placements and promo sections. Null values preserve
the prior banner-first/product-second arrangement, using existing type-specific ordering.
After saving a shared arrangement, unpositioned new blocks append in that legacy order.
Individual product-list move controls direct homepage editors to the shared page; separate
ordering still applies to other placements. Banner editor priority is the default for
unpositioned assignments. Banner attachments inside product sections remain part of the
parent section and are not copied into the standalone list.

The server checks administrator authorization, distinct integer positions, an exact match
against current homepage membership, and a revision hash before updating the participating tables.
Writes use one transaction and row locks in consistent table/primary-key order. A stale
form or a forged/foreign block key is rejected without partially changing the arrangement.
Deleting a block removes it naturally; no independent ordering records need cleanup.
Publication, scheduling, exact banner counts, collection visibility and stock rules remain
unchanged. Existing per-type public limits are applied after shared positions within that
type, so high-priority blocks are not accidentally excluded before composition.

### Deferred rendering

The initial homepage reads eligible promo metadata to reserve the correct slots; product
rows are still deferred. Public banners and slots share one captured cutoff and are merged
by their shared positions. Only a banner occupying the first visible block receives an
eager/high-priority image; banners following a product block are lazy.

All product slots share one `/home/promo-sections` request. The existing bounded query loads
products in batches and the existing Blade fragment marks each section by ID. Each slot
selects its own section from that response; it never relocates the already rendered banners.
Later slots reuse the response body, and failed/redirected/invalid responses permit a fresh
retry. A section removed between initial page load and fragment loading hides only its own
slot. Existing carousel, comparison, wishlist and quantity initialization remains in use.
The fragment remains `private, no-store`, with no application cache or external request.

### Verification

- Entire PHP suite: **815 tests passed / 10007 assertions**. The final normalization change
  was additionally checked with all six shared-layout tests (56 assertions).
- All **16 frontend test files** passed, including shared-request reuse, body reuse, retry
  after failures/invalid responses, safe slot selection and removed-section handling.
- Production Vite build, Blade compilation, Pint and whitespace checks passed.
- Browser: an actual admin saved banner/product/banner/product order on isolated test data;
  both product slots loaded in place, with exactly one product-fragment request in server logs.
  Homepage and the admin editor fit a 390px viewport without page overflow; the editor's
  Vietnamese labels rendered correctly. No console errors were recorded.
- A pre-existing flaky admin-order test used random email digits that could legitimately
  match an order-number keyword. Its two buyer emails are now fixed demonstration addresses;
  no order search behavior was changed.

The normal additive migration was applied to development, retaining all null positions and
therefore its existing order. Browser reorder edits used only `larpshop_testing`. No production
data, seed placement, commit, merge, push or deployment was changed. Apply the new migration
and rebuild frontend assets when deploying this follow-up.

### Interactive sections in the shared layout

The complete layout also includes these reserved, translated entries:

- `system:broad_select`: **Broad Select · product carousel**.
- `system:category_discovery`: **Category discovery · AJAX product selection**.

Both move through the same order form as banners and standalone product sections.
Broad Select's tabs, carousel rows and attached banners stay within its single block;
category discovery's filters, expand/retry controls and results stay within its block.
They cannot be duplicated or replaced with arbitrary components through form input.
Existing asset imports and frontend initializers are unchanged. Each component is
rendered exactly once, from the ordered loop rather than a fixed position after it.

Migration `2026_11_22_000003_create_homepage_system_blocks.php` adds a small settings table
for the two nullable positions. Null positions preserve their previous location after
banners and ordinary promos. No products, collections, tabs or content are copied.
The service provides read-only in-memory defaults if configuration rows are missing;
only an explicit authorized save creates those missing rows. Missing settings therefore
cannot remove built-in homepage sections or cause a GET to write layout configuration.
The same transaction, row locks, membership checks and revision validation cover these
positions. Unknown system keys are rejected, and stale forms must be reloaded.

Browser verification used disposable data in `larpshop_testing`: the admin saved
carousel → banner → category discovery → products → banner → products. Switching the
carousel tab still selected its matching panel. Choosing Electronics in category
discovery loaded only the matching product without navigating away from `/`. At a
390px viewport, homepage and the Vietnamese order editor remained 375px wide, each
interactive component occurred once, and there were no console errors. The temporary
browser tab and server were closed afterward. Development received only the additive
migration, with default positions; no existing arrangement was changed.

Final regression after this extension: **818 PHP tests passed / 10035 assertions**,
including nine layout tests; all **16 frontend test files**, the production build,
Blade compilation, Pint and whitespace checks passed.
