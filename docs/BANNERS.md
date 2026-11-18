# Banner implementation stages

## Stage 1: Data and placement (implemented)

- `banners` uses `banner_id` and a required `vendor_id` foreign key. Ownership is assigned through `Vendor::banners()`, not mass-assigned from request input.
- `config/banners.php` is the zone registry. Initially `vendor_header` belongs to the vendor context, uses a 4:1 aspect ratio, and displays at most one banner. This is a display limit, not a limit on stored drafts or scheduled alternatives.
- Store metadata includes title, alt text, storage disk/path, intrinsic width/height, optional link, optional schedule, an inactive-by-default flag, and ascending sort order.
- `BannerQuery::visibleForVendor()` scopes by vendor and zone, excludes unpublished vendors and inactive banners, evaluates the schedule, orders by `sort_order` then `banner_id`, and applies the configured display limit.
- Start is inclusive; expiry is exclusive. Null bounds are unrestricted. Compare full timestamps in the application's date/time convention, never dates alone. Convert user-entered scheduling times explicitly in the future write layer.
- Vendor deletion is restricted while banners exist. Future deletion services must coordinate database deletion and file cleanup; database cascades cannot remove stored files.
- No upload endpoint, management UI, image processor, rendering component, or cache is introduced in this stage. Zone validity, allowed link schemes, positive image dimensions, and valid schedule intervals must be validated by future write requests/services. Configuration supplies the crop ratio; the database stores actual output dimensions.
- Homepage/category contexts require explicit schema and authorization extensions. They are not represented as unowned vendor banners.

## Stage 2: Crop component and processing service (implemented)

`<x-image-cropper>` is a standalone Blade component backed by a native custom element. It accepts a field name, aspect ratio, output width, source byte limit and pixel limit. It does not query banners or know about vendors. Bootstrap styling uses at most `rounded-2`; semantic backgrounds support light/dark themes. No new JavaScript dependency is required.

The storefront settings banner section currently offers a preview only. The editor remains inline inside the existing settings drawer, avoiding a second modal and competing focus traps. Select JPEG/PNG/WebP, drag or use keyboard-accessible zoom/position sliders, reset, clear, then explicitly confirm the crop. Only the confirmed PNG file is assigned to the named form input. Editing invalidates it; pending async operations cannot overwrite newer selections. Source images are decoded locally, and browser EXIF orientation is applied. The pixel limit is checked after browser decoding; it is a UI safeguard, not a server security boundary.

The current vendor header outputs 1600 × 400. `BannerImageProcessor::store()` accepts the cropped PNG/WebP upload, validates byte limits and exact output dimensions before decoding, re-encodes it as WebP, and returns disk/path/dimensions. It does not write banner rows. Future management services must authorize ownership before processing and delete the new image if the database operation fails, or delete the old image after a successful replacement.

### Runtime and verification

- PHP GD with PNG/WebP support is required; the Dockerfile now builds GD with JPEG/WebP support. Rebuild the app image before using this service.
- On an Ubuntu PHP 8.4 host, install `php8.4-gd` with administrator privileges, then verify `php --ri gd`. GD was initially unavailable during stage 2; it was present for stage 3 and stage 5, where real WebP processing passed.
- `php artisan test --filter=BannerImageProcessorTest` covers malformed images, incorrect dimensions, file limits and WebP storage. The WebP test skips explicitly when GD is unavailable; run it again after enabling GD.
- `node --test tests/js/image_crop_geometry.test.mjs` checks portrait/wide crops, zoom bounds and aspect ratio.
- `npm run build` validates the frontend bundle. A standalone browser preview verified image selection, confirmation, zoom invalidation and reset. Full management submission belongs to stage 3.

### Stage 2 files

- `resources/views/components/image-cropper.blade.php`
- `resources/js/components/image_cropper.js`
- `resources/js/components/image_crop_geometry.js`
- `resources/css/components/image_cropper.css`
- `resources/views/components/vendor/storefront-settings.blade.php`
- `app/Services/Banners/BannerImageProcessor.php`
- `config/banners.php`, `Dockerfile`
- `lang/en/banners.php`, `lang/vi/banners.php`
- `tests/Feature/Vendor/BannerImageProcessorTest.php`
- `tests/js/image_crop_geometry.test.mjs`
- `docs/BANNERS.md`

## Stage 3: Vendor management (implemented)

- Owner-only storefront data is loaded via `BannerQuery::managedByVendor()` in the page controller and passed through the header/settings components. Blade components do not query the database.
- `banner-management` renders preview cards, schedule, zone, priority, explicit enable/disable and delete actions. `banner-editor` shares create/edit fields and the cropper. New records start disabled in the UI. Keeping the image unchanged requires no upload on edit.
- `VendorBannerController` uses separate save, toggle and management requests. Routes are scoped under `/vendor/stores/{vendor}/banners`; scoped binding prevents cross-store banner IDs. Every operation verifies the authenticated owner and the store's active/approved status. The service repeats ownership/status checks under a row lock.
- Titles/alt text are required; vendor zones are allowlisted, links allow HTTP/HTTPS only, dates use minute precision in `config('app.timezone')`, expiry must follow start, and priority is a nonnegative bounded integer. The single initial zone has fixed crop dimensions. Adding zones with different ratios will also require zone-change cropper synchronization and validation of retained image dimensions.
- Creation/replacement uses the image processor. A failed database write removes the newly stored file. Old images and deleted banners' images are removed after commit. Cleanup failures are reported without converting a committed update into a failed request; automatic cleanup retries are a future operational concern.
- Enable/disable sets an explicit target state, so repeated submissions do not flip unexpectedly. Priority is edited numerically; drag/drop is deferred because the initial zone displays only one banner.
- After saving, the browser returns to the vendor storefront and opens the existing settings drawer. Validation preserves metadata in the relevant editor; files must be selected/cropped again. Deletion uses the existing translated form confirmation mechanism.
- Public banner rendering belongs to stage 4. No persistent development migration was run in this stage; apply the stage 1 migration before opening owner banner management on the development database.

### Stage 3 files

- `app/Http/Controllers/Vendor/VendorBannerController.php`
- `app/Http/Controllers/VendorStorefrontController.php`
- `app/Http/Requests/Banners/{ManageVendorBannerRequest,SaveVendorBannerRequest,ToggleVendorBannerRequest}.php`
- `app/Services/Banners/BannerManagementService.php`
- `app/Queries/Banners/BannerQuery.php`
- `resources/views/components/{banner-editor,banner-management,persistent-header}.blade.php`
- `resources/views/components/vendor/storefront-settings.blade.php`
- `resources/views/vendor/pages/storefront.blade.php`
- `routes/vendor.php`, `lang/en/banners.php`, `lang/vi/banners.php`
- `tests/Feature/Vendor/VendorBannerManagementTest.php`
- `docs/BANNERS.md`

## Stage 4: Public presentation (implemented)

- `VendorStorefrontController` supplies `headerBanners` through `BannerQuery::visibleForVendor()`. The storefront renders it above the store profile. No active/scheduled banner means no section or reserved empty space.
- `<x-banner-zone zone="vendor_header" :banners="$headerBanners" :priority="true" />` takes prepared records, reads presentation configuration and performs no queries. The query is responsible for visibility, ownership context, ordering and configured display limits.
- `<x-banner-image>` provides storage URLs, escaped alt text, intrinsic dimensions, configured aspect ratio, responsive width and optional HTTP/HTTPS links. Unsafe stored link schemes are omitted as a defense beyond write validation.
- A single banner renders directly. Multiple supplied banners reuse Bootstrap 6's scroll-snap carousel and its existing data API, with uniquely targeted previous/next controls. No autoplay or additional slider dependency is introduced. The initial `vendor_header` query still returns at most one banner; multi-banner rendering is available for future configurations.
- Priority mode eagerly loads only the first image with high fetch priority. Other images default to lazy loading. Responsive image variants and query caching are implemented in stage 5.
- `VendorBannerDisplayTest` covers public visibility, isolation, priority, empty zones, escaped content, unsafe links and multiple component instances. A standalone browser preview verified the Bootstrap next control.

### Stage 4 files

- `resources/views/components/banner-zone.blade.php`
- `resources/views/components/banner-image.blade.php`
- `app/Http/Controllers/VendorStorefrontController.php`
- `resources/views/vendor/pages/storefront.blade.php`
- `lang/en/banners.php`, `lang/vi/banners.php`
- `tests/Feature/Vendor/VendorBannerDisplayTest.php`
- `docs/BANNERS.md`

## Stage 5: Caching and responsive images (implemented)

- `BannerCache` maintains a versioned namespace per vendor. Keys include vendor, zone and configured display limit. Invalidating a vendor rotates its namespace for all zones; a concurrent older read cannot repopulate the new namespace. Old entries expire naturally (at most `banners.cache_seconds`, default one hour).
- Cache payload v2 stores only scalar raw model attributes and arrays, preserving JSON/date strings. Reads hydrate Eloquent models without another banner query; class unserialization stays disabled. The v2 key prefix bypasses legacy object entries automatically; malformed payloads are rebuilt.
- Live queries cache their result, including empty collections, only until the earlier of the configured TTL or the next enabled banner start/expiry boundary. Full timestamp comparisons preserve inclusive starts and exclusive ends. Explicit-time reads bypass the live cache.
- `BannerObserver` invalidates after commit on model saves/deletes, capturing both previous and new vendor IDs. Zone moves invalidate all zones for the affected vendor. `VendorBannerObserver` does the same when status changes. Bulk query-builder updates/deletes do not fire model observers; future bulk operations must explicitly invalidate affected vendors. Public reads are outside write transactions; do not use cached reads to preview uncommitted edits.
- The image processor creates the master WebP and smaller variants configured by the zone (480px/960px for the 1600px vendor header). It never upscales variants. Partial processing/storage failures remove all newly written files.
- The additive `image_variants` JSON migration preserves existing records. New uploads/replacements store variant metadata; existing banners remain usable without variants. No automatic backfill or persistent-development migration was run.
- Banner replacement, deletion and rollback cleanup include all variants. The display component emits width-based `srcset` with a configurable `sizes` prop (default `100vw`). The first priority banner loads eagerly; subsequent/below-fold images are lazy. Set `sizes` to the intended layout width when embedding in narrower contexts.
- Cache failures follow the application's configured cache behavior; file cleanup failures are reported as described in stage 3.

### Stage 5 files

- `app/Services/Banners/BannerCache.php`
- `app/Observers/BannerObserver.php`, `app/Observers/VendorBannerObserver.php`
- `app/Providers/AppServiceProvider.php`, `app/Queries/Banners/BannerQuery.php`
- `app/Services/Banners/BannerImageProcessor.php`, `app/Services/Banners/BannerManagementService.php`
- `app/Models/Banner.php`, `config/banners.php`
- `database/migrations/2026_11_17_000001_add_image_variants_to_banners_table.php`
- `resources/views/components/banner-image.blade.php`, `resources/views/components/banner-zone.blade.php`
- `tests/Feature/Vendor/{VendorBannerCacheTest,BannerImageProcessorTest,VendorBannerManagementTest,VendorBannerDisplayTest}.php`
- `docs/BANNERS.md`

All five implementation stages are complete. For deployment, run `php artisan migrate`, ensure the public storage link exists, build frontend assets, and provide PHP GD with WebP support. Existing images do not require re-uploading unless responsive variants are desired.

## Verification

Run `php artisan test --filter=VendorBannerQueryTest` against the configured isolated test database. The migration has not been applied to the persistent development database by this implementation.

## Integration onto `real`

The banner and storefront-settings changes were consolidated onto `real` at `b39b347`, without replaying the older mixed localization history. Existing responsive header spacing, working quick links, authentication/storefront JavaScript imports, OPcache configuration, profile purchase translations and newer user model behavior are retained.

The shared settings drawer now lives in `components/settings-panel.blade.php`, called through `locale-panels`, so both public storefront and admin layouts have exactly one settings drawer. Its language section opens by default; owner-specific storefront settings remain guarded by authentication/ownership checks.

The localization drawer regression assertion now recognizes the language section ID. The existing persistent-header keyboard test fixture calls the window focus listener, matching the unchanged implementation.

## Cache serialization regression coverage

`VendorBannerDatabaseCacheTest` runs the cache behavior suite with the database store and `cache.serializable_classes=false`, including cold/warm public storefront requests. It verifies model/date/JSON restoration, plain-data payloads, legacy entry isolation and malformed entry recovery. The ordinary array cache tests remain in place for query reuse. Do not cache Eloquent objects or cast date objects directly in this payload; change the key schema version when the payload contract changes.
