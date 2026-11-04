# Product review stored XSS demonstration

This deliberately vulnerable change belongs only on the `intentionally-vuln`
branch for local security demonstrations with disposable test accounts.

## Affected component and root cause

- **Routes:** `GET /products/{product}/{slug?}` (`products.show`) and `GET /products/{product}/reviews` (`reviews.index`)
- **Submission route:** `POST /products/{product}/reviews` (`reviews.store`), handled by `ProductReviewController::store()`
- **Blade component:** `resources/views/components/products/review.blade.php`

`StoreProductReviewRequest` validates that `comment` is a string up to 2,000
characters, and `ProductReviewService` stores the comment as submitted. In the
secure implementation, Blade's `{{ $review->comment }}` syntax encodes HTML
special characters via `htmlspecialchars()`.

This demonstration replaces `{{ $review->comment }}` with raw unescaped Blade
output `{!! $review->comment !!}`. Because review comments are persisted in
`product_reviews.comment` and rendered to every visitor viewing the product page,
any HTML markup submitted by a verified buyer is interpreted directly by the
browser.

Review eligibility checks (requiring a paid order for the product) and CSRF
protection remain unchanged.

## Local reproduction

1. Sign in to the local application with a disposable customer account that has a
   `paid` order for a product.
2. Open the product detail page (`/products/<product-id>/<slug>`) and locate the
   **Write a review** form.
3. Submit a rating (for example `5`) and a comment containing harmless HTML
   formatting markup (for example `<strong>Highlighted review note</strong>`).
4. Reload the product detail page or open it from a second browser session.
5. Observe that the comment is rendered as live HTML DOM elements inside the
   review card rather than escaped literal characters.

Clean up or delete the disposable review record from the local database after
completing the demonstration.

## Existing regression test

Run against the isolated test database configured by `phpunit.xml`:

```bash
php artisan test --filter=test_public_reviews_are_paginated_escaped_and_do_not_claim_unverified_purchases
```

The test asserts `assertDontSee('<script>review-5</script>', false)` to verify
that raw HTML tags are escaped in the response body. On this branch, that
assertion fails because `{!! $review->comment !!}` outputs unescaped markup. Keep
that security assertion unchanged so the test suite detects the vulnerability.

## Remediation

Restore standard Blade escaping in `resources/views/components/products/review.blade.php`:

```blade
@if ($review->comment)
    <p class="mb-0 text-break" style="white-space: pre-line;">{{ $review->comment }}</p>
@else
    <p class="mb-0 fg-3">This customer left a rating without a comment.</p>
@endif
```

If rich-text formatting is ever required in the future, sanitize stored HTML on
the server using an explicit allowlist sanitizer (such as HTMLPurifier) before
rendering. Rerun `ProductReviewTest` after restoring `{{ $review->comment }}`.

## Changed files

- `resources/views/components/products/review.blade.php`
- `docs/STORED_XSS_DEMO.md`
