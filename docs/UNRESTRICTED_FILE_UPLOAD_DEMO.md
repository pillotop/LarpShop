# Unrestricted product image upload demonstration

This deliberately vulnerable change belongs only on the `intentionally-vuln`
branch for local security demonstrations with disposable test accounts.

## Affected endpoints and root cause

- `POST /vendor/products/{product}/images` (`vendor.products.images.store`),
  handled by `VendorProductImageController::store()` via
  `UploadProductImageRequest` and `ProductImageManagementService::uploadImages()`
- `POST /products/{product}/images` (`products.images.store`), handled by
  `ProductImageController::store()`

In the secure implementation:
1. Uploaded files are validated using Laravel's `image` and
   `mimes:jpeg,png,jpg,webp,gif` rules (which inspect server-side MIME types and
   exclude active formats such as SVG or scripts).
2. Files are persisted via `$file->store('products', 'public')`, which generates
   a randomized hash filename and derives a safe extension from the detected MIME
   type.

This demonstration weakens both controls:
- Validation is relaxed to `'file'` instead of `'image', 'mimes:jpeg,png,jpg,webp,gif'`.
- Storage calls `$file->storeAs('products', $file->getClientOriginalName(), 'public')`,
  trusting the client-supplied filename and extension directly under
  `storage/app/public/products/`.

Vendor authentication and product ownership checks on the vendor route remain
enabled.

## Local reproduction

1. Sign in to the local application with a disposable vendor account and open
   `/vendor/products/<product-id>/images`.
2. Select a non-raster file (such as a `.svg` or `.txt` test file) in the upload
   form and submit it.
3. Observe that the upload succeeds and stores the file on the `public` disk
   under its original client-supplied filename (`storage/products/<filename>`).
4. Delete the uploaded test image from the gallery after completing the
   demonstration.

## Regression test

Run against the isolated test database and fake storage disk configured by
`phpunit.xml`:

```bash
php artisan test --filter=test_vendor_cannot_upload_non_image_files
```

The test intentionally asserts secure behavior: non-allowlisted file types such
as `.svg` must be rejected with validation errors on `images.0` and must not
create a `ProductImage` record. It fails on this branch because `'file'` accepts
arbitrary uploads. Do not weaken that assertion.

## Remediation

1. Restore strict MIME and image validation in `UploadProductImageRequest::rules()`:

```php
return [
    'images' => ['required', 'array', 'min:1'],
    'images.*' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
    'alt_text' => ['nullable', 'string', 'max:255'],
];
```

2. Restore randomized hash storage in `ProductImageManagementService::uploadImages()`
   and `ProductImageController::store()`:

```php
$path = $file->store('products', 'public');
```

3. Restore `'image' => ['required', 'image', 'max:5120']` in
   `ProductImageController::store()`. Rerun `VendorProductImageManagementTest`
   to verify that the regression test passes.

## Changed files

- `app/Http/Controllers/ProductImageController.php`
- `app/Http/Requests/Vendor/UploadProductImageRequest.php`
- `app/Services/Products/ProductImageManagementService.php`
- `tests/Feature/Vendor/VendorProductImageManagementTest.php`
- `docs/UNRESTRICTED_FILE_UPLOAD_DEMO.md`
