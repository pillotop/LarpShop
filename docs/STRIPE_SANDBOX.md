# Stripe Checkout sandbox

## Scope and architecture

Stripe Checkout provides hosted credit/debit card entry. LarpShop receives no card
number, expiry or CVC. This integration accepts test API keys only and rejects
live sessions and live webhook events. PayPal and Fake Payment remain unchanged.

The first integration supports USD payments from $0.50 to $999,999.99. Display
currency does not change the payment currency. Adaptive Pricing is disabled so
Stripe does not silently change the authoritative payment currency. Amounts are
converted to cents using integer/string arithmetic, without floating-point math.

Responsibilities:

- `StripeClient`: Laravel HTTP calls to the fixed Stripe API host, timeouts,
  test-key enforcement, API version `2025-06-30.basil` and idempotency keys.
- `StripePaymentGateway`: creates sessions and maps verified Stripe session data
  to the existing PaymentSession/PaymentResult objects. It does not capture a
  PayPal-style authorization; Checkout handles card payment itself.
- `StripeWebhookHandler`: SDK signature verification of the raw payload, event
  filtering, payment/session correlation and a fresh Stripe session read.
- `PaymentService`: existing order/payment locks and state transitions, plus an
  event receipt committed atomically with each webhook-driven transition.
- Checkout card component: a server-rendered form redirecting to Stripe.

No migration or gateway-contract changes are required. The existing payment event
table stores provider event IDs. Duplicates are checked again under the payment
row lock. Browser return requests remain read-only; already-confirmed returns
redirect to order details. The existing confirmation action and scheduled payment
reconciliation can also verify the current Stripe session.

The webhook route is public and excluded from CSRF only at `/webhooks/stripe`.
Stripe's signature is required instead. Other payment POST routes retain auth,
ownership checks and CSRF protection. HTTP failures return a retryable response
without exposing Stripe's response body. Unknown application events are ignored;
notifications arriving before local session persistence receive a retryable error.

## Setup you perform

The PHP SDK is installed through Composer. Stripe CLI is a separate development
tool, not an application dependency. No account was registered, no Dashboard
settings were changed, and no real Stripe API requests were made during development.

1. Open the Stripe Dashboard and select a sandbox/test environment. Obtain its
   **secret test API key**. The application accepts `sk_test_...` and `rk_test_...`
   keys. A publishable key is not needed for this hosted redirect integration.
2. Install the [official Stripe CLI](https://docs.stripe.com/cli). Authenticate it
   yourself, and select the same sandbox as the application's API key. Current CLI
   versions provide `stripe login`, `stripe login list` and `stripe switch`.
3. Start the application normally, then keep this listener running in another
   terminal (adjust the port to your local server):

   ```bash
   stripe listen \
     --events checkout.session.completed,checkout.session.async_payment_succeeded,checkout.session.async_payment_failed,checkout.session.expired \
     --forward-to http://127.0.0.1:8000/webhooks/stripe
   ```

4. Copy the listener's `whsec_...` signing secret into your local `.env`, together
   with your sandbox API key:

   ```dotenv
   STRIPE_ENABLED=true
   STRIPE_SECRET_KEY=your_secret_test_key
   STRIPE_WEBHOOK_SECRET=your_listener_signing_secret
   PAYMENT_CURRENCY=USD
   ```

5. Run `php artisan config:clear`. Restart long-running workers after configuration
   changes. Keep the application server and webhook listener running while testing.

Store both secrets locally; do not commit them or paste them into a chat. The CLI
listener secret differs from a Dashboard endpoint signing secret. If you instead
register a public HTTPS endpoint in the Dashboard, use that endpoint's signing
secret and the same four Checkout Session event types.

`.env.example` is currently ignored by this repository. Its local Stripe variables
were updated, but this tracked document is the portable configuration reference.

## Manual sandbox check

1. Create an order through the cart and choose a shipping address.
2. Select **Credit or Debit card**, then **Continue to Stripe**.
3. Use Stripe test card `4242 4242 4242 4242`, a future expiry and any three-digit CVC.
4. Finish payment and confirm that the order becomes paid and appears in order
   history. A webhook can confirm it even if the customer does not return.
5. Repeat with a separate order and declined test card `4000 0000 0000 0002`.
   A card decline can be retried inside the still-open Stripe session; it does not
   by itself mark the entire Checkout Session as terminally failed.
6. Leaving Stripe with its cancel link keeps the unpaid attempt resumable. An
   actually expired Stripe session becomes cancelled and permits a new attempt.

For more scenarios, use [Stripe's test card documentation](https://docs.stripe.com/testing).
Synthetic CLI events without the metadata and session ID of a LarpShop payment
are intentionally ignored; use an actual Checkout Session created by LarpShop for
end-to-end validation.

## Boundaries

Order reservation expiry remains authoritative. Stripe's session expiry and the
local order deadline are independent. If payment arrives after the order closes
or inventory is released, the existing flow records `refund_required` rather than
fulfilling that order. Automatic refunds are not included in this integration.

The merchant return/cancel URLs are derived on the server. No client-supplied
amount, currency, provider reference or return status can confirm payment.
The integration does not enable other currencies, subscriptions, marketplace
payouts, embedded card forms or additional providers.

## Verification

Automated tests fake all Stripe HTTP calls and use the isolated testing database.
They cover authoritative amounts, integer cents, repeated creation, stable retry
keys, ownership, read-only returns, manual confirmation, enabled/disabled setup,
test-key enforcement, signed/invalid/expired webhooks, duplicate events, late and
out-of-order results, async recovery, refund review, CSRF scope and localization.
The complete PHP suite passes 579 tests with 7,503 assertions.

Real Stripe sandbox verification still requires the account/key/listener setup
above and has not been performed.

## Files changed

- composer.json and composer.lock
- config/payments.php and config/services.php
- bootstrap/app.php and routes/payments.php
- app/Services/Payments/StripeClient.php
- app/Services/Payments/StripePaymentGateway.php
- app/Services/Payments/StripeWebhookHandler.php
- app/Services/Payments/PaymentGatewayFactory.php
- app/Services/Payments/PaymentService.php
- app/Http/Controllers/Payments/StripeWebhookController.php
- app/Http/Controllers/Payments/CheckoutPaymentController.php
- app/Http/Requests/Payments/CreatePaymentRequest.php
- resources/views/components/checkout/payment-methods/card.blade.php
- lang/en/checkout.php and lang/vi/checkout.php
- tests/Support/CreatesStripePayments.php
- tests/Feature/Payments/StripeCheckoutTest.php
- tests/Feature/Payments/StripeWebhookTest.php
- README.md and this document

Official references: [Checkout Session creation](https://docs.stripe.com/api/checkout/sessions/create?api-version=2025-06-30.basil),
[fulfillment](https://docs.stripe.com/checkout/fulfillment),
[webhook verification](https://docs.stripe.com/events/manage-webhook-endpoints).
