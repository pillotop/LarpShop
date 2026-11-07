# Localization

## Request flow

The application supports English (`en`, default) and Vietnamese (`vi`).
Supported languages are defined in `config/app.php`.

1. The web middleware group starts the session.
2. `SetLocale` runs immediately after `StartSession`, before authentication and
   route model binding. It reads the session selection, or the configured application default.
   Invalid session values use `app.fallback_locale`.
3. Blade resolves translation keys for the current request.
4. `POST /locale` validates the selection against `app.supported_locales`,
   stores it in the session, and redirects back.

The switch endpoint is public and protected by CSRF. Login session regeneration
preserves the selection. Logout currently invalidates the session and clears it.
There is no saved account preference or cross-device synchronization.

## Responsibilities

- Middleware selects the locale; it does not translate content.
- The controller validates and stores the preference.
- PHP language files contain application text, grouped by domain.
- Blade renders translated labels and passes only needed messages to JavaScript.
- Database identifiers, enum values, sort keys, URLs, prices, and currency remain
  independent of the selected language.

Use `__()` for text and named placeholders. Use `trans_choice()` for counts.
Keep English and Vietnamese catalogs structurally aligned. Laravel falls back
to English for a missing Vietnamese entry; if neither locale contains a key,
Laravel displays the key itself.

## Language controls

`<x-locale-panels>` renders two instances of `<x-side-panel>` once per page:

- `language-panel`: bottom drawer opened from the company footer, without blur.
- `settings-panel`: right drawer opened from the persistent header, containing
  a Language accordion item. The admin top bar opens the same Settings drawer.

Both use `<x-locale-switcher>`. The cart keeps its independent drawer.
`locale_panels.js` moves the locale drawers under `body` during initialization,
before the opening interaction, to preserve CSS transitions. Bootstrap owns
opening, closing, focus management, and slide animations.

Opening drawers and toggling the accordion do not navigate. Applying a language
reloads the page so Laravel can render the new language.

## Catalog milestone

Implemented translation domains:

- `products.php`: homepage editorial banners, catalog/search/filter labels,
  product cards, category pages, product detail labels and vendor status labels.
- `reviews.php`: review display and shared review form labels.
- `wishlist.php`: shared wishlist action labels.

Vendor-authored product names, descriptions, category names, store names,
variant names, image descriptions, reviewer names, and review comments remain
unchanged. Homepage promotional banner copy is application-authored and translated.

Sort labels are translated in Blade using the allowlisted sort keys; queries
retain their existing sort contract. Review pagination sends translated loading
and error messages through `data-*` attributes, including subsequent AJAX pages.
Review timestamps explicitly use the request locale.

Shared review forms and wishlist buttons also show translated labels where
reused outside the catalog. Their surrounding pages are not fully translated yet.

## Remaining scope

The agreed customer-flow milestones are complete. Additional UI work requires
a separate checkpoint:
- Vendor/admin management pages outside the profile store section.
- Static password recovery/reset page copy. Login/registration and shared
  frontend authentication state messages are localized.
- Static informational page bodies are excluded at the user's request.
- Notification/email templates.
- Vietnamese translations for unused framework validation rules; those
  intentionally fall back to the complete English Laravel catalog.

At the catalog checkpoint, cart feedback was deferred. The cart/checkout
checkpoint below now covers it with one shared message contract.

## Verification

```sh
php artisan test --filter='ProfileOrderLocalizationTest|ProfileTest|ChangePasswordTest|OrderStatusConsistencyTest|AccountVendorTest|ProfileAddressTest|WishlistTest|ProductReviewTest|OrderLifecycleTest|LocaleTest|CartCheckoutLocalizationTest'
php artisan test --filter='CartCheckoutLocalizationTest|CheckoutTest|CheckoutAddressTest|CartTest|CheckoutPageTest|PaymentFlowTest|PaymentGatewayContractTest|PayPalCheckoutTest|PaymentSecurityTest|CatalogLocalizationTest'
node tests/Frontend/cart_messages.test.mjs
npm run build
```

Database-backed feature tests use the isolated MySQL `larpshop_testing` database
configured in `phpunit.xml`.

Catalog checkpoint verification: 77 focused tests passed with 316 assertions.
The frontend build passed. Browser checks covered Vietnamese search results,
filter labels and selected sort values, product details, and review display.

## Catalog checkpoint file list

Translation catalogs (each in both `lang/en` and `lang/vi`):
- `products.php`
- `reviews.php`
- `wishlist.php`

Pages:
- `resources/views/homepage.blade.php`
- `resources/views/products/pages/index.blade.php`
- `resources/views/products/pages/show.blade.php`
- `resources/views/categories/pages/show.blade.php`

Components:
- `resources/views/components/category-banner.blade.php`
- `resources/views/components/product-card-a.blade.php`
- `resources/views/components/product-card-b.blade.php`
- `resources/views/components/rating-container.blade.php`
- `resources/views/components/two-arrow-scroller.blade.php`
- `resources/views/components/products/filter-panel.blade.php`
- `resources/views/components/products/review.blade.php`
- `resources/views/components/products/reviews-section.blade.php`
- `resources/views/components/review-form.blade.php`
- `resources/views/components/wishlist/button.blade.php`

Integration, tests, and documentation:
- `resources/views/products/partials/review-list.blade.php`
- `resources/js/components/product_reviews.js`
- `routes/web.php` (Home breadcrumb label)
- `tests/Feature/CatalogLocalizationTest.php`
- `docs/LOCALIZATION.md`

## Cart and checkout checkpoint

Implemented:
- Full cart page, cart drawer, sorting announcements, quantity controls, and
  order summary labels.
- Contact details, saved shipping address selection, order summary, reservation
  timestamps, payment methods, simulated outcomes, and provider return page.
- Display labels for order/payment statuses. Stored enum values and provider
  option identifiers are unchanged.
- Custom cart, order creation, inventory, and payment errors plus checkout
  success notifications.

The shared layout renders only `cart.js` messages in an escaped JSON script.
`cart_messages.js` reads that dictionary and substitutes named placeholders.
The cart store, cart view, and quantity picker use the same dictionary.
Server-rendered AJAX fragments independently use the session locale.

The quantity picker's success message now reads the existing `quantity` response
field, replacing its reference to the nonexistent `item_quantity` field.

Prices, currency formatting, authorization, reservations, payment transitions,
request validation rules, and provider calls retain their behavior.
At the cart checkpoint, generic validation rules still used English. The
validation checkpoint below adds Vietnamese messages for the rules used by
the customer flows.

Verification:
- 73 focused feature tests passed, with 513 assertions.
- 3 JavaScript tests passed for Unicode messages, named placeholders, connection
  failure notifications, and malformed responses preserving cart state.
- Frontend build and whitespace checks passed.
- Browser checks covered Vietnamese cart add/remove, AJAX fragments and sorting
  announcements; the temporary cart item was removed afterward.

At this checkpoint, the older `CheckoutTest` produced 6 failures and 1 error
because its requests omitted the required `address_id`. The follow-up test
maintenance below resolves that mismatch without changing the checkout API.

### Cart/checkout file list

Translation catalogs:
- `lang/en/cart.php`, `lang/vi/cart.php`
- `lang/en/checkout.php`, `lang/vi/checkout.php`
- `lang/en/common.php`, `lang/vi/common.php` (Guest label)

Frontend modules:
- `resources/js/components/cart_messages.js`
- `resources/js/components/cart_store.js`
- `resources/js/components/cart_view.js`
- `resources/js/components/product_quantity_picker.js`

Views:
- `resources/views/shared/cart-messages.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/cart/pages/index.blade.php`
- `resources/views/cart/partials/content.blade.php`
- `resources/views/components/cart.blade.php`
- `resources/views/components/cart/order-summary.blade.php`
- `resources/views/components/cart/product-card.blade.php`
- `resources/views/components/user-settings.blade.php`
- `resources/views/checkout/pages/show.blade.php`
- `resources/views/checkout/pages/payment-return.blade.php`
- `resources/views/components/checkout/details-section.blade.php`
- `resources/views/components/checkout/address-section.blade.php`
- `resources/views/components/checkout/order-summary.blade.php`
- `resources/views/components/checkout/payment-section.blade.php`
- `resources/views/components/checkout/payment-actions.blade.php`
- `resources/views/components/checkout/payment-methods/card.blade.php`
- `resources/views/components/checkout/payment-methods/simulated.blade.php`
- `resources/views/components/checkout/payment-methods/wallet.blade.php`

Backend (user-facing messages only):
- `app/Http/Controllers/CartController.php`
- `app/Http/Controllers/CheckoutController.php`
- `app/Http/Controllers/Payments/CheckoutPaymentController.php`
- `app/Http/Controllers/Payments/FakePaymentController.php`
- `app/Services/Cart/CartMutationService.php`
- `app/Services/Orders/OrderCreationService.php`
- `app/Services/Orders/OrderAddressService.php`
- `app/Services/Inventory/InventoryReservationService.php`
- `app/Services/Payments/PaymentService.php`

Tests and documentation:
- `tests/Feature/CartCheckoutLocalizationTest.php`
- `tests/Frontend/cart_messages.test.mjs`
- `docs/LOCALIZATION.md`

## Checkout address test maintenance

The existing checkout regression tests now create an owned shipping address
and include its ID. They still verify authoritative prices, stock changes,
empty-cart rejection, transaction rollback, guest access, and duplicate checkout.
Rollback assertions now include `order_addresses`.

A separate `CheckoutAddressTest` covers:
- Missing or malformed IDs, even when a default address exists.
- Addresses belonging to another customer and deleted addresses.
- Rejected requests leaving the cart, stock, orders, details and snapshots unchanged.
- Selecting a non-default address and copying all its address fields.
- Ignoring client-supplied address text in favor of the saved record.
- Keeping the order snapshot unchanged after the saved address is edited or deleted.

`CreatesCheckoutFixtures` supplies a complete saved address fixture.
The existing page tests continue covering address switching and locking after
payment starts or the order expires.

Verification: 43 tests passed with 375 assertions across `CheckoutTest`,
`CheckoutAddressTest`, `CheckoutPageTest`, `CartCheckoutLocalizationTest`,
and `ProfileAddressTest`. No application code or checkout contract changed.

Files:
- `tests/Feature/CheckoutTest.php`
- `tests/Feature/CheckoutAddressTest.php`
- `tests/Support/CreatesCheckoutFixtures.php`
- `docs/LOCALIZATION.md`

## Profile and customer orders checkpoint

Implemented:
- Profile title, breadcrumb, account role labels, contact form and security tab.
- Store tabs and registration/resubmission forms embedded in the profile. Store
  names and descriptions remain unchanged; vendor management pages are separate.
- Saved address forms, default address controls and wishlist content.
- Customer order history, detail and cancellation labels, payment display labels,
  and paid-purchase review forms.
- Shared pagination labels, result ranges and accessible page links.
- Success notifications and custom business errors in these flows.

Profile, order, wishlist, review and pagination catalogs retain separate
responsibilities. Order/payment status labels reuse the existing checkout
catalog; store status labels reuse the existing catalog labels rather than
introducing duplicate translations.

Machine-readable session markers such as `profile-updated`,
`password-updated`, `vendor-registered` and `wishlist-updated` remain unchanged.
Form names, IDs, old-input restoration, review eligibility, permissions, amounts
and stored statuses retain their existing contracts. The cancellation status
flash contains a display message, so that message is translated.

The address field definitions and input attributes in `billing-tab` were
expanded over multiple lines while preserving the existing validation metadata.

Verification:
- 86 focused tests passed with 536 assertions, including English regressions.
- Vietnamese tests cover profile forms and unchanged data, wishlist tab markers,
  address success messages, history pagination and paid-purchase reviews, and
  cancellation with stock restoration.
- Formatting and whitespace checks passed.
- Generic framework validation catalogs were deferred to the validation checkpoint below.
- Authenticated interfaces were verified through feature tests; no browser
  sign-in or live customer-account mutations were performed.

### Profile/order file list

Translation catalogs (English and Vietnamese):
- `lang/{en,vi}/profile.php`
- `lang/{en,vi}/orders.php`
- `lang/{en,vi}/wishlist.php`
- `lang/{en,vi}/reviews.php`
- `lang/{en,vi}/pagination.php`

Views:
- `resources/views/profile/pages/show.blade.php`
- `resources/views/components/profile/profile-tab.blade.php`
- `resources/views/components/profile/security-tab.blade.php`
- `resources/views/components/profile/hero-card.blade.php`
- `resources/views/components/profile/vendor-tab.blade.php`
- `resources/views/components/profile/vendor-store-pane.blade.php`
- `resources/views/components/profile/vendor-registration-form.blade.php`
- `resources/views/components/profile/billing-tab.blade.php`
- `resources/views/components/profile/wishlist-tab.blade.php`
- `resources/views/components/wishlist/product.blade.php`
- `resources/views/components/orders/review-products.blade.php`
- `resources/views/orders/pages/index.blade.php`
- `resources/views/orders/pages/show.blade.php`
- `resources/views/shared/pagination.blade.php`

Backend (user-facing messages only):
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/AddressController.php`
- `app/Http/Controllers/AccountVendorController.php`
- `app/Http/Controllers/Auth/ChangePasswordController.php`
- `app/Http/Controllers/WishlistController.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/ProductReviewController.php`
- `app/Services/Reviews/ProductReviewService.php`
- `app/Services/Vendors/VendorManagementService.php`
- `app/Services/Orders/OrderLifecycleService.php`
- `routes/web.php` (Profile breadcrumb)

Tests and documentation:
- `tests/Feature/ProfileOrderLocalizationTest.php`
- `docs/LOCALIZATION.md`

## Validation and translation audit checkpoint

Implemented:
- Vietnamese framework validation messages for customer-facing rules, including
  type-specific numeric/string/array/file bounds and password rules.
- Application field labels in both languages, including shipping address,
  quantity, review rating, search filters and locale.
- Removal of the generated example custom validation entry from the English
  catalog. Its complete framework rules remain the English fallback.
- Vietnamese authentication and password broker catalogs, plus translated
  custom login/reset responses. Anti-enumeration wording is preserved.
- Translated remaining checkout stock and PayPal validation errors. Internal
  diagnostic exceptions remain English.
- Translated footer metadata and shared breadcrumb labels. Footer links and
  their destination pages retain their existing URLs.
- Vietnamese JSON translations for Laravel's exception summary strings only.
  Application messages continue to use domain PHP catalogs.

Validation keys, named error bags, status codes, old input, validation rules
and redirect behavior are unchanged. No user preference is sent to PayPal;
a fake-HTTP regression test verifies the exact experience context and absence
of an Accept-Language header. No real provider request is made in that test.

The Vietnamese validation catalog deliberately covers the rules currently
used by the customer flows. Untranslated framework rules fall back to English.
A fallback regression test explicitly verifies this behavior.

Verification:
- 225 focused tests passed with 2,810 assertions.
- The key audit checks literal application translation keys in PHP/Blade for
  both locales. Dynamic labels for every order/payment enum value are checked
  separately.
- Catalog checks compare keys and named placeholders in all application domains;
  Vietnamese validation entries must exist in English and preserve placeholders.
- Functional tests cover localized JSON summaries, saved-address error bags and
  old input, password failures, checkout/review validation, stock rejection,
  login rejection, footer labels and unchanged destinations.
- Formatting and whitespace checks passed.
- No frontend JS/CSS build inputs changed in this checkpoint.

### Validation/audit file list

Language files:
- `lang/en/validation.php`, `lang/vi/validation.php`
- `lang/en/auth.php`, `lang/vi/auth.php`
- `lang/en/passwords.php`, `lang/vi/passwords.php`
- `lang/en/checkout.php`, `lang/vi/checkout.php`
- `lang/en/common.php`, `lang/vi/common.php`
- `lang/en/footer.php`, `lang/vi/footer.php`
- `lang/vi.json`

Application and shared views:
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Auth/ForgotPasswordController.php`
- `app/Http/Controllers/Auth/ResetPasswordController.php`
- `app/Services/Payments/PayPalClient.php`
- `app/Services/Payments/PayPalPaymentGateway.php`
- `app/Services/Orders/OrderCreationService.php`
- `app/View/Components/CompanyFooter.php`
- `resources/views/components/company-footer.blade.php`
- `resources/views/shared/breadcrumbs.blade.php`
- `routes/web.php`

Tests and documentation:
- `tests/Feature/ValidationLocalizationTest.php`
- `tests/Feature/TranslationCatalogTest.php`
- `docs/LOCALIZATION.md`

## Login page checkpoint

Implemented:
- Login page title, navigation labels, promotional copy and accessible home link.
- Email, sign-in and registration form labels and controls.
- Localized state headers, loading messages and request/network fallback errors.
- A small `auth_messages.js` module reads the Blade JSON dictionary; the state
  machine retains its existing state names and request behavior.
- Fetch TypeError failures use the localized network error rather than the
  browser's English error text.

The shared script also runs on password recovery/reset pages. Their components
receive the same message dictionary to keep those states working, but their
static page/form copy is deferred. No backend authentication, registration,
validation or routing code changed in this checkpoint.
The unused legacy `login_form.js` was not changed.

Static informational pages remain excluded from this work at the user's request.

Verification:
- 44 related tests passed with 1,797 assertions; this is a focused group, not
  the whole project suite.
- Tests cover English/Vietnamese login rendering, all state dictionaries,
  the existing email lookup contract, invalid and successful login,
  registration validation and session locale persistence.
- Shared password-reset tests and translation audits pass.
- Browser checks verified Vietnamese registration state and returning to email;
  no account was created or password submitted during browser verification.
- Frontend build, formatting and whitespace checks passed.

Files:
- `lang/en/auth.php`
- `lang/vi/auth.php`
- `resources/views/auth/pages/login.blade.php`
- `resources/views/components/auth/login-form.blade.php`
- `resources/views/components/auth/forgot-password-form.blade.php` (dictionary only)
- `resources/views/components/auth/reset-password-form.blade.php` (dictionary only)
- `resources/views/auth/partials/messages.blade.php`
- `resources/js/components/auth_messages.js`
- `resources/js/components/auth_form.js`
- `tests/Feature/AuthPageLocalizationTest.php`
- `docs/LOCALIZATION.md`

## Completion checkpoint — functional pages

This checkpoint completes localization of the currently routed functional UI.
It supersedes the password-recovery deferral in the previous checkpoint.
Static informational page bodies remain intentionally excluded.

Completed:
- Password recovery and reset page titles, instructions, labels and actions.
- Admin dashboard, store approval/settings, categories, users/roles, order
  investigation, product/review moderation, and reports.
- Vendor store settings, product listings/editing, stock filters, images,
  variants, categories, order fulfillment, reports, and public storefront.
- User-facing admin/vendor service errors, ownership failures, validation field
  names, success messages and confirmation prompts.
- Fulfillment display labels, independent of stored enum values and transitions.
- Report ranges and availability labels resolved in Blade from allowlisted keys;
  query constants and filter request values remain unchanged.
- Password reset email content and shared mail layout labels.
- Payment confirmation notifications capture the active locale when created;
  amount, currency, order ID and action URL retain their authoritative values.
- Localized framework error-page titles and the default JSON authorization error.
- An admin Settings trigger uses the existing language drawer component.
  `forceDrawer` lets language panels render dialogs even on admin routes without
  duplicating the admin sidebar or changing existing sidebar callers.

Responsibility boundaries:
- `management.php` holds shared management labels, form help, report/filter
  options, fulfillment display labels, and backend messages. Related copy and
  messages are grouped; dynamic sentences use named parameters.
- `auth.php` owns password-recovery copy; `notifications.php` owns the payment
  email. `vi.json` contains framework-owned literal strings only.
- `confirmation_forms.js` handles forms marked with `data-confirm` through one
  delegated submit listener. Blade escapes translated text in attributes; the
  script contains no application copy or interpolated inline JavaScript.
- Locale selection still reloads the page after Apply. Opening either panel and
  expanding Settings do not reload the page.

Verification:
- **All 348 PHP tests passed, with 5,361 assertions.** This is the entire configured
  PHPUnit suite, not only localization tests. Tests use `larpshop_testing`.
- New tests render admin and vendor pages in both languages, verify a real
  Settings dialog and a single admin sidebar, retain internal form/status values,
  exercise localized validation and ownership denial, and check mail rendering.
- A missing-product test verifies locale middleware precedes route model binding.
- Translation audits cover matching keys/placeholders and all order, payment,
  and fulfillment status labels.
- Both relevant frontend test files passed (cart messages and delegated
  confirmations; six individual test cases).
- Vite production build, scoped Pint formatting and whitespace checks passed.
- Browser verification checked the Vietnamese recovery page and bottom language
  table. No reset email was requested through the browser and no live payment
  request was made.

Remaining boundaries:
- Static page bodies are left for the owner. Shared navigation/footer labels are
  translated, but this does not translate the content of those pages.
- User-entered names, descriptions, reviews, addresses and other database content
  are displayed as authored. Prices and currencies are not converted.
- The preference is session-scoped, clears on logout, and is not synchronized
  across devices. No database schema or preferred-locale user field was added.
- A queued payment notification preserves its construction locale. A notification
  created from a webhook/background job with no customer session uses the
  application's configured default; recovering a customer's historical choice
  would require a persisted preference, outside the current design.
- Vietnamese validation covers the rules used by active forms; unused framework
  rules retain the tested English fallback.
- Unused Breeze/prototype views and scripts were not migrated or removed.
  Global `view:cache` still encounters the pre-existing unused Breeze layout's
  missing `application-logo` component. Active routed views are covered by the
  rendering tests above; the partial compiled-view cache was cleared afterward.

Files changed in this checkpoint:

- `README.md`
- `app/Enums/FulfillmentStatus.php`
- `app/Http/Controllers/Admin/AdminCategoryController.php`
- `app/Http/Controllers/Admin/AdminOrderController.php`
- `app/Http/Controllers/Admin/AdminProductModerationController.php`
- `app/Http/Controllers/Admin/AdminReviewModerationController.php`
- `app/Http/Controllers/Admin/AdminVendorController.php`
- `app/Http/Controllers/ProductImageController.php`
- `app/Http/Controllers/Vendor/VendorCategoryController.php`
- `app/Http/Controllers/Vendor/VendorOrderController.php`
- `app/Http/Controllers/Vendor/VendorProductController.php`
- `app/Http/Controllers/Vendor/VendorProductImageController.php`
- `app/Http/Controllers/Vendor/VendorProductVariantController.php`
- `app/Http/Controllers/Vendor/VendorStoreController.php`
- `app/Http/Controllers/VendorStorefrontController.php`
- `app/Http/Middleware/Admin.php`
- `app/Http/Middleware/EnsureActiveVendor.php`
- `app/Notifications/PaymentSucceededNotification.php`
- `app/Services/Categories/CategoryManagementService.php`
- `app/Services/Orders/OrderManagementService.php`
- `app/Services/Products/ProductImageManagementService.php`
- `app/Services/Users/UserManagementService.php`
- `app/Services/Vendors/VendorOrderFulfillmentService.php`
- `app/Services/Vendors/VendorProductService.php`
- `app/Services/Vendors/VendorProductVariantService.php`
- `bootstrap/app.php`
- `docs/LOCALIZATION.md`
- `lang/en/auth.php`
- `lang/en/management.php`
- `lang/en/notifications.php`
- `lang/en/validation.php`
- `lang/vi.json`
- `lang/vi/auth.php`
- `lang/vi/management.php`
- `lang/vi/notifications.php`
- `lang/vi/validation.php`
- `resources/js/app.js`
- `resources/js/components/confirmation_forms.js`
- `resources/views/admin/categories/index.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/products/moderation.blade.php`
- `resources/views/admin/reports/index.blade.php`
- `resources/views/admin/reviews/index.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/vendors/edit.blade.php`
- `resources/views/admin/vendors/index.blade.php`
- `resources/views/auth/pages/forgot-password.blade.php`
- `resources/views/auth/pages/reset-password.blade.php`
- `resources/views/components/admin/category-filter.blade.php`
- `resources/views/components/admin/category-form-fields.blade.php`
- `resources/views/components/admin/category-table.blade.php`
- `resources/views/components/admin/dashboard-metrics.blade.php`
- `resources/views/components/admin/order-filter-tabs.blade.php`
- `resources/views/components/admin/order-summary-cards.blade.php`
- `resources/views/components/admin/order-table.blade.php`
- `resources/views/components/admin/order-vendor-breakdown.blade.php`
- `resources/views/components/admin/pending-vendor-requests.blade.php`
- `resources/views/components/admin/recent-active-vendors.blade.php`
- `resources/views/components/admin/side-panel.blade.php`
- `resources/views/components/admin/user-filter-tabs.blade.php`
- `resources/views/components/admin/user-table.blade.php`
- `resources/views/components/admin/vendor-filter-tabs.blade.php`
- `resources/views/components/admin/vendor-table.blade.php`
- `resources/views/components/auth/forgot-password-form.blade.php`
- `resources/views/components/auth/reset-password-form.blade.php`
- `resources/views/components/locale-panels.blade.php`
- `resources/views/components/side-panel.blade.php`
- `resources/views/components/vendor/category-form.blade.php`
- `resources/views/components/vendor/image-card.blade.php`
- `resources/views/components/vendor/product-form.blade.php`
- `resources/views/components/vendor/store-switcher.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/vendor/categories/create.blade.php`
- `resources/views/vendor/categories/edit.blade.php`
- `resources/views/vendor/categories/index.blade.php`
- `resources/views/vendor/orders/index.blade.php`
- `resources/views/vendor/orders/show.blade.php`
- `resources/views/vendor/pages/storefront.blade.php`
- `resources/views/vendor/products/create.blade.php`
- `resources/views/vendor/products/edit.blade.php`
- `resources/views/vendor/products/images.blade.php`
- `resources/views/vendor/products/index.blade.php`
- `resources/views/vendor/products/variants/index.blade.php`
- `resources/views/vendor/reports/index.blade.php`
- `resources/views/vendor/store/settings.blade.php`
- `tests/Feature/AdminVendorTest.php`
- `tests/Feature/ManagementLocalizationTest.php`
- `tests/Feature/RecoveryNotificationLocalizationTest.php`
- `tests/Feature/TranslationCatalogTest.php`
- `tests/Frontend/confirmation_forms.test.mjs`
