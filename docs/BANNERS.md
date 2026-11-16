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

## Stage 2: Crop component and processing service (implemented; WebP runtime verification pending)

`<x-image-cropper>` is a standalone Blade component backed by a native custom element. It accepts a field name, aspect ratio, output width, source byte limit and pixel limit. It does not query banners or know about vendors. Bootstrap styling uses at most `rounded-2`; semantic backgrounds support light/dark themes. No new JavaScript dependency is required.

The storefront settings banner section currently offers a preview only. The editor remains inline inside the existing settings drawer, avoiding a second modal and competing focus traps. Select JPEG/PNG/WebP, drag or use keyboard-accessible zoom/position sliders, reset, clear, then explicitly confirm the crop. Only the confirmed PNG file is assigned to the named form input. Editing invalidates it; pending async operations cannot overwrite newer selections. Source images are decoded locally, and browser EXIF orientation is applied. The pixel limit is checked after browser decoding; it is a UI safeguard, not a server security boundary.

The current vendor header outputs 1600 × 400. `BannerImageProcessor::store()` accepts the cropped PNG/WebP upload, validates byte limits and exact output dimensions before decoding, re-encodes it as WebP, and returns disk/path/dimensions. It does not write banner rows. Future management services must authorize ownership before processing and delete the new image if the database operation fails, or delete the old image after a successful replacement.

### Runtime and verification

- PHP GD with PNG/WebP support is required; the Dockerfile now builds GD with JPEG/WebP support. Rebuild the app image before using this service.
- On the current Ubuntu PHP 8.4 host, install `php8.4-gd` with administrator privileges, then verify `php --ri gd`. This session could not install it because sudo requires interactive authentication; Docker socket access was also denied.
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

## Remaining stages (each requires review and approval)

3. Vendor banner management, ownership checks, validation, replacement/deletion cleanup and ordering where supported.
4. Reusable presentation components with data supplied through queries; integrate the vendor storefront first.
5. Context-aware cache invalidation after successful transactions, expiry at schedule boundaries, responsive image variants and appropriate loading priority.

## Verification

Run `php artisan test --filter=VendorBannerQueryTest` against the configured isolated test database. The migration has not been applied to the persistent development database by this implementation.
