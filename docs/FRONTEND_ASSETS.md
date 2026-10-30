# Frontend assets

LarpShop uses npm and Vite to build Bootstrap, Bootstrap Icons, project CSS, and browser JavaScript. Laravel continues to render Blade pages.

## Development

Use a Node version supported by the installed Vite release (verified with Node 22.13.1).

```bash
npm ci
npm run dev
```

Run Laravel separately. Blade's `@vite` directive reads the Vite development server URL from `public/hot` while the server is running.

## Production build

```bash
npm ci
npm run build
```

The build writes minified assets with content hashes, icon fonts, and a manifest to `public/build`. Deploy this directory together with the Laravel application. A running Node server is not required to serve the production build. Rebuild after changing CSS or JavaScript.

## Source and loading conventions

- Keep CSS in `resources/css` and JavaScript in `resources/js`.
- `vite.config.js` discovers CSS and JavaScript entries recursively. Literal wildcard strings are not used as entry filenames.
- Common layouts load `resources/css/app.css` and `resources/js/app.js`.
- `app.css` imports Bootstrap's compiled CSS and Bootstrap Icons before custom styles.
- `app.js` exposes `window.bootstrap` for existing Blade handlers and initializes the shared cart modules.
- Page component modules import `../app.js` so shared initialization runs before their own code. The two cart modules are loaded by `app.js` itself and do not import it back.
- Load page-specific assets through grouped `@vite([...])` directives. Keep layout-wide assets in the layout.
- JavaScript entries are ES modules and execute after HTML parsing. Top-level declarations are local to each module; explicitly export/import shared code or deliberately expose a global when required.
- Public image URLs remain under `/images` and uploaded files remain under `/storage`.
- Snap.svg and Google Fonts still use their existing CDN URLs. Bootstrap and Bootstrap Icons are served from the Vite build.

Legacy raw HTML prototypes under `resources/views/*.html` retain their original markup; active Blade views use Vite. Old JavaScript copies in `public/js` remain for those prototypes. Application changes belong in `resources/js`.

## Verification

- Production build succeeds and all 30 asset entry paths referenced by Blade exist in the manifest.
- Browser smoke checks: homepage and login render without JavaScript console errors; the cart offcanvas opens and closes using the npm Bootstrap build.
- Feature regression checks: 38 tests passed, 252 assertions (`ProductDetailTest`, `CheckoutPageTest`, `AccountVendorTest`, `AdminOrderTest`).
- Full `view:cache` currently fails on the existing missing Breeze component `application-logo`. The view cache was cleared after this check.

## Review locations

- `vite.config.js`, `package.json`, and `package-lock.json`.
- `resources/css/app.css`, `resources/css/login_contents.css`, and the styles moved from `public/css` into `resources/css` (including `components/`).
- `resources/js/app.js` and `resources/js/components/`.
- Blade asset declarations in `resources/views/layouts/`, `auth/pages/`, `cart/pages/`, `categories/pages/`, `checkout/pages/`, `products/pages/`, `profile/pages/`, `static/`, `vendor/`, `misc/pages/supports.blade.php`, and `homepage.blade.php`.
