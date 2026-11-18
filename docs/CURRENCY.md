# Display currency

Customers can independently choose their language and display currency in the
Settings drawer. `POST /currency` validates USD/VND and stores `display_currency`
in the session. The default is USD; language changes do not change currency.

## Responsibilities

- `config/currency.php` defines the supported display currencies and rate endpoint.
- `ExchangeRateService` retrieves the USD/VND rate from the public
  [ExchangeRate-API endpoint](https://www.exchangerate-api.com/docs/free).
  Successful responses are cached for 24 hours; unavailable or invalid responses
  are cached for five minutes to avoid repeated calls. No API key is required.
- `MoneyDisplay` is scoped to the request and resolves a consistent effective
  currency for that response. Conversion uses decimal arithmetic and rounds half
  up: USD has two decimal places; VND has none. Existing USD formatting is
  preserved; VND grouping follows the selected language.
- A view composer supplies `$money` to Blade. Use
  `{{ $money->format($product->price) }}` for customer-facing USD amounts.
- `CartController` adds `cart.formatted_total` for JavaScript while preserving
  the numeric `cart.total_amount` field and the other cart data. Row prices are
  rendered by the same formatter in the returned Blade fragments.

If rates cannot be retrieved, the page displays USD and a visible notice.
Browsing and checkout do not depend on the rate provider being available. Pages
using converted prices include the provider attribution. A cold cache may add up
to three seconds to the first VND request.

## Accounting boundary

This feature changes presentation only. Product prices, cart numeric amounts,
orders, payments, refunds, and provider requests remain in USD. Checkout displays
the authoritative USD payment total alongside its converted estimate. Simulated
payment buttons and payment confirmations retain the actual payment currency.
Catalog price filter inputs, vendor price editing, and management reports also
remain in USD. No database migration is needed.

Changing the setting submits the form and reloads the page. Other open pages
reload when their next cart refresh detects a different effective display currency.
The existing cart visibility and BroadcastChannel handlers trigger those refreshes;
no separate currency messaging channel is required.

## Verification

Run `php artisan test tests/Feature/CurrencyTest.php tests/Feature/CurrencyCheckoutTest.php`.
HTTP calls are faked in tests; the checkout test verifies that VND display leaves
product, order, and payment amounts in USD. Database tests use `larpshop_testing`.
