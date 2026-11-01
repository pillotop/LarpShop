# Bootstrap 6 migration

## Version and scope

The application now pins Bootstrap to **6.0.0-alpha.1**, explicitly selected for this project. It is a prerelease, not Bootstrap 6 stable. Bootstrap Icons remains at 1.11.3. Floating UI replaces Popper as the positioning dependency.

This migration includes shared components and the active Blade views that consume them, along with public, profile, checkout, vendor, and admin pages. Historical raw HTML prototypes and old public JavaScript copies are not application entrypoints; current assets come from resources through Vite.

## Theme configuration

- `resources/scss/app.scss` loads Bootstrap through `@use ... with (...)`.
- `bootstrap/_colors.scss` retains the project palette.
- `bootstrap/_theme.scss` merges that palette with Sass's map module and defines semantic color roles: base, foreground, background, border, focus, and contrast.
- `bootstrap/_utilities.scss` exposes project palette utilities such as `.fg-primary-300`, `.bg-secondary-300`, and the existing `--color-*` tokens.
- Existing spacing values for 0–5 are supplied explicitly to preserve the current layout scale.
- Bootstrap 6's default viewport breakpoints and prefix syntax are used. Corresponding project CSS queries were updated.
- Bootstrap's source tokens are unprefixed (for example, `--primary-base`), while browser data attributes retain the `data-bs-*` prefix.
- Sass deprecation suppression for the old import-based setup was removed.

The shared layouts still load theme SCSS, project CSS, and application JavaScript in that order. Do not add precompiled Bootstrap CSS alongside the theme.

## Component changes

- Responsive classes use prefixes, such as `lg:col-6` and `md:d-flex`.
- Buttons use a style and a theme, such as `btn btn-solid theme-primary` or `btn btn-outline theme-danger`.
- Semantic foreground colors use `fg-*`. Background opacity uses `bg-25` rather than `bg-opacity-25`.
- Account dropdown markup uses Menu and Floating UI, including placement on the trigger.
- Side panels use native `<dialog class="drawer ...">` elements and drawer data attributes.
- Modals use native Dialog markup without the old positioning wrappers.
- Accordions use `<details>` and `<summary>`. The cart stores disclosure state through `details.open` and captures native toggle events, keeping expanded details when server fragments refresh.
- Profile navigation uses the tab data API.
- Select fields use `form-control`; checkbox, radio, and switch inputs use their component classes directly.
- Autoplay uses the Bootstrap 6 carousel attribute.
- Product media frames retain fitted images while native aspect ratios control their proportions.
- Application pagination and breadcrumbs use local Bootstrap 6 templates rather than framework-provided Bootstrap 5/Tailwind markup.

The custom product category dropdown remains a separate vanilla JavaScript component.

## Build and runtime

```bash
npm ci
npm run build
```

For development, run `npm run dev` alongside Laravel, or use the Docker development override documented in the README. Rebuild Docker images so they receive the updated lockfile and assets.

Bootstrap 6 requires modern browsers: Chrome/Edge 130+, Firefox 132+, and Safari 18+. Review upstream alpha changes before future upgrades.

## Verification

- Production Vite build passes without Sass deprecation warnings.
- 139 focused regression tests pass with 698 assertions, covering cart, reviews, product details/search, account/vendor, wishlist, checkout, admin orders/categories, vendor products, homepage, static pages, categories, and storefronts.
- 121 Blade templates compile and pass PHP syntax checks.
- Four existing Breeze views cannot compile because their application-logo or input-label components are missing; those legacy components were not restored by this migration.
- Browser smoke checks passed for homepage render, adding a product, cart badge updates, drawer opening/closing, native product-detail disclosure, and removal. The guest test cart was restored to empty.
- No JavaScript console errors were observed during those browser checks.
- Authenticated Menu, Dialog, and profile tab interactions were not exhaustively exercised in the browser; their pages are covered by the server-side regression checks where applicable.

This is a focused migration verification, not a claim that every repository test passes.

Reference: [Bootstrap 6 migration guide](https://getbootstrap.com/docs/6.0/guides/migration/).

## Files to review

The following working-tree list includes the Sass setup changes that were already pending when this migration began, plus the Bootstrap 6 changes.

- `README.md`
- `app/Providers/AppServiceProvider.php`
- `config/breadcrumbs.php`
- `docs/BOOTSTRAP_6_MIGRATION.md`
- `docs/FRONTEND_ASSETS.md`
- `package-lock.json`
- `package.json`
- `resources/css/app.css`
- `resources/css/components/product_card.css`
- `resources/css/preview_box.css`
- `resources/js/components/cart_view.js`
- `resources/js/components/product_quantity_picker.js`
- `resources/scss/app.scss`
- `resources/scss/bootstrap/_colors.scss`
- `resources/scss/bootstrap/_theme.scss`
- `resources/scss/bootstrap/_utilities.scss`
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
- `resources/views/auth/pages/login.blade.php`
- `resources/views/auth/pages/reset-password.blade.php`
- `resources/views/cart/pages/index.blade.php`
- `resources/views/cart/partials/content.blade.php`
- `resources/views/categories/pages/category.blade.php`
- `resources/views/categories/pages/show.blade.php`
- `resources/views/checkout/pages/payment-return.blade.php`
- `resources/views/checkout/pages/show.blade.php`
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
- `resources/views/components/auth/login-form.blade.php`
- `resources/views/components/auth/reset-password-form.blade.php`
- `resources/views/components/cart.blade.php`
- `resources/views/components/cart/order-summary.blade.php`
- `resources/views/components/cart/product-card.blade.php`
- `resources/views/components/category-banner.blade.php`
- `resources/views/components/checkout/address-section.blade.php`
- `resources/views/components/checkout/details-section.blade.php`
- `resources/views/components/checkout/order-summary.blade.php`
- `resources/views/components/checkout/payment-actions.blade.php`
- `resources/views/components/checkout/payment-methods/card.blade.php`
- `resources/views/components/checkout/payment-methods/simulated.blade.php`
- `resources/views/components/checkout/payment-methods/wallet.blade.php`
- `resources/views/components/checkout/payment-section.blade.php`
- `resources/views/components/company-footer.blade.php`
- `resources/views/components/modal.blade.php`
- `resources/views/components/nav-bar.blade.php`
- `resources/views/components/orders/review-products.blade.php`
- `resources/views/components/product-card-a.blade.php`
- `resources/views/components/product-card-b.blade.php`
- `resources/views/components/products/filter-panel.blade.php`
- `resources/views/components/products/review.blade.php`
- `resources/views/components/products/reviews-section.blade.php`
- `resources/views/components/profile/billing-tab.blade.php`
- `resources/views/components/profile/hero-card.blade.php`
- `resources/views/components/profile/nav-tabs.blade.php`
- `resources/views/components/profile/profile-tab.blade.php`
- `resources/views/components/profile/security-tab.blade.php`
- `resources/views/components/profile/vendor-registration-form.blade.php`
- `resources/views/components/profile/vendor-store-pane.blade.php`
- `resources/views/components/profile/vendor-tab.blade.php`
- `resources/views/components/profile/wishlist-tab.blade.php`
- `resources/views/components/rating-container.blade.php`
- `resources/views/components/review-feedback.blade.php`
- `resources/views/components/review-form.blade.php`
- `resources/views/components/side-panel.blade.php`
- `resources/views/components/user-settings.blade.php`
- `resources/views/components/vendor/category-form.blade.php`
- `resources/views/components/vendor/image-card.blade.php`
- `resources/views/components/vendor/product-form.blade.php`
- `resources/views/components/vendor/store-switcher.blade.php`
- `resources/views/components/wishlist/button.blade.php`
- `resources/views/components/wishlist/product.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/homepage.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/misc/pages/supports.blade.php`
- `resources/views/orders/pages/show.blade.php`
- `resources/views/products/pages/index.blade.php`
- `resources/views/products/pages/show.blade.php`
- `resources/views/products/partials/review-list.blade.php`
- `resources/views/profile/pages/delete-user-form.blade.php`
- `resources/views/profile/pages/show.blade.php`
- `resources/views/profile/pages/update-password-form.blade.php`
- `resources/views/profile/pages/update-profile-information-form.blade.php`
- `resources/views/shared/breadcrumbs.blade.php`
- `resources/views/shared/pagination.blade.php`
- `resources/views/static/company/about.blade.php`
- `resources/views/static/company/blog.blade.php`
- `resources/views/static/company/contact.blade.php`
- `resources/views/static/company/investors.blade.php`
- `resources/views/static/company/press.blade.php`
- `resources/views/static/customer-service/_layout.blade.php`
- `resources/views/static/customer-service/community.blade.php`
- `resources/views/static/customer-service/help.blade.php`
- `resources/views/static/customer-service/privacy.blade.php`
- `resources/views/static/customer-service/recalls.blade.php`
- `resources/views/vendor/categories/create.blade.php`
- `resources/views/vendor/categories/edit.blade.php`
- `resources/views/vendor/categories/index.blade.php`
- `resources/views/vendor/orders/index.blade.php`
- `resources/views/vendor/orders/show.blade.php`
- `resources/views/vendor/pages/create_product.blade.php`
- `resources/views/vendor/pages/storefront.blade.php`
- `resources/views/vendor/products/create.blade.php`
- `resources/views/vendor/products/edit.blade.php`
- `resources/views/vendor/products/images.blade.php`
- `resources/views/vendor/products/index.blade.php`
- `resources/views/vendor/products/variants/index.blade.php`
- `resources/views/vendor/reports/index.blade.php`
- `resources/views/vendor/store/settings.blade.php`
- `resources/views/welcome.blade.php`
- `vite.config.js`
