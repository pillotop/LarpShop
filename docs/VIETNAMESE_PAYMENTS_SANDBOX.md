# Vietnamese payment sandboxes

LarpShop supports hosted sandbox checkout with MoMo, ZaloPay and VNPAY, alongside
PayPal and Stripe. VNPAY is a bank/card/QR gateway, so the checkout group is labelled
"Wallets & bank payments" rather than implying every provider is a wallet.

All three integrations use fixed sandbox API hosts. They do not implement refunds
or enable production payments. Provider onboarding and an actual sandbox purchase
still need to be completed with your own merchant credentials.

## 1. Obtain sandbox credentials

Use separate merchant credentials for this application. Do not copy shared demo
keys from examples or commit your keys to Git.

| Provider | Credentials | Official setup |
| --- | --- | --- |
| MoMo | Partner Code, Access Key, Secret Key | [Merchant profile](https://developers.momo.vn/v3/docs/payment/onboarding/merchant-profile/) and [integration process](https://developers.momo.vn/v3/docs/payment/onboarding/integration-process/) |
| ZaloPay | App ID, Key1, Key2 | [Sandbox onboarding](https://docs.zalopay.vn/vi/docs/developer-tools/integration-guide/); sandbox access may require contacting ZaloPay support |
| VNPAY | Terminal Code, Hash Secret | [Sandbox merchant registration](https://sandbox.vnpayment.vn/devreg) |

MoMo and ZaloPay wallet testing may require their sandbox mobile applications and
test accounts. Follow each provider's current test instructions; regular wallet
accounts are not interchangeable with sandbox accounts.

## 2. Configure the application

Add the following settings to your local, ignored `.env`. Leave providers disabled
until their own credentials and public callbacks are ready.

```dotenv
PAYMENT_CURRENCY=USD

MOMO_ENABLED=false
MOMO_PARTNER_CODE=
MOMO_ACCESS_KEY=
MOMO_SECRET_KEY=

ZALOPAY_ENABLED=false
ZALOPAY_APP_ID=
ZALOPAY_KEY1=
ZALOPAY_KEY2=

VNPAY_ENABLED=false
VNPAY_TMN_CODE=
VNPAY_HASH_SECRET=
```

Set the relevant `*_ENABLED` flag to `true` after filling in its sandbox credentials.
Restart long-running queue/scheduler processes after changing configuration.

Apply the additive settlement migration and clear cached configuration:

```bash
php artisan migrate
php artisan config:clear
```

Use `migrate`, not `migrate:fresh`: existing orders and payments are preserved.
The migration adds nullable `source_amount`, `source_currency`, and `exchange_rate`
columns to `payments`. Existing PayPal, Stripe and fake payments remain compatible.

## 3. Provide publicly reachable HTTPS callbacks

Run the application normally, then expose its port through your own HTTPS tunnel
or use an HTTPS staging environment. Configure `APP_URL` to that public origin,
clear cached configuration, and open the checkout through that same origin.
Using the same origin preserves your browser session when the provider returns.

For an example origin `https://your-sandbox.example`, callback endpoints are:

| Provider | Server callback | Browser return |
| --- | --- | --- |
| MoMo | `POST https://your-sandbox.example/webhooks/momo` | Generated `/payments/{payment}/return` |
| ZaloPay | `POST https://your-sandbox.example/webhooks/zalopay` | Generated `/payments/{payment}/return` in `embed_data.redirecturl` |
| VNPAY | `GET https://your-sandbox.example/webhooks/vnpay` | Generated `/payments/{payment}/return` |

MoMo and ZaloPay callback URLs are sent with the create request. Configure or
allowlist them in your merchant portal if required. VNPAY's IPN URL must be
registered with VNPAY; it is not the browser return URL. Reconfigure the merchant
portal whenever your tunnel hostname changes.

Stripe CLI forwarding only forwards Stripe events. It does not forward these
three providers' callbacks. Localhost URLs are not reachable from their servers.

These callback routes intentionally do not require a customer login or CSRF token.
They require the provider's valid signature. Ordinary checkout and confirmation
POST requests retain login, ownership and CSRF protection. Browser return query
parameters never mark an order paid.

## 4. Settlement currency and amounts

Catalog/order totals currently use the application's payment base currency, USD.
The three Vietnamese providers settle in whole VND. Changing the display currency
or language does not change this rule.

When creating a new Vietnamese payment attempt, `SettlementQuoteService` reads the
cached USD/VND exchange rate through `ExchangeRateService`, converts the current
database order total using decimal arithmetic, and rounds half up to whole dong.
It freezes all of these values on the payment:

- `source_amount` and `source_currency`: the original order amount/currency;
- `exchange_rate`: the rate used for conversion;
- `amount` and `currency`: the actual VND settlement amount.

For example, USD 49.95 at 25,000 VND/USD becomes VND 1,248,750. The order still
contains USD 49.95. MoMo/ZaloPay receive the integer `1248750`; VNPAY requires
`124875000` (dong multiplied by 100).

Retries reuse the original payment and quote, even if rates change or become
unavailable. New attempts require a rate; there is no silent 1:1 fallback. Current
application limits are VND 1,000–50,000,000 for MoMo/ZaloPay and VND
5,000–50,000,000 for VNPAY, configured per provider in `config/payments.php`.
Merchant-specific restrictions can still reject an amount within these limits.

## 5. Payment handling and recovery

The gateways handle provider protocols, while `PaymentService` retains ownership
of the payment/order state transitions. Signed callbacks validate the merchant,
reference, settlement amount and currency before updating anything. Event receipts
and state changes are stored in one transaction under row locks, so repeated
callbacks do not fulfill an order or send its success notification twice.

The application persists a deterministic merchant reference before initiating an
external request. A callback can therefore complete a payment even if the create
request timed out. Retries retain the same reference and, for ZaloPay/VNPAY, the
original transaction date in Vietnam time.

If an initialization response was lost and the provider reports a duplicate, the
adapter queries that same transaction. If the original checkout URL cannot be
recovered, the UI still offers a status check rather than creating another active
payment. A genuinely unpaid transaction may need to expire before starting a new
attempt. Do not manually mark it failed merely because an HTTP request failed.

Customers can use the existing payment status-check action. The existing
`payments:reconcile` scheduled command also queries these providers through the
same gateway contract. Keep the scheduler running for eventual reconciliation:

```bash
php artisan schedule:work
```

ZaloPay insufficient-balance responses remain pending because the customer can
top up and retry. Provider configuration/query errors are not treated as final
payment failures. Payments received after order cancellation or expiry become
`refund_required`; they do not reopen the order or reserve stock again. This is
an existing review state, not an implemented refund operation.

## 6. Verify before considering a provider ready

Automated tests fake all provider HTTP requests and need no merchant account:

```bash
php artisan test --filter='Wallet(Checkout|Webhook|Protocol)Test'
php artisan test --filter=Payments
npm run build
```

Tests use the separate `larpshop_testing` MySQL database from `phpunit.xml`.
Coverage includes request signatures, quote snapshots, authoritative prices,
amount tampering, disabled providers, ownership, callback signatures, duplicate
callbacks, initialization timeouts, provider-query verification, and late success.

After configuring each real sandbox account:

1. Start with a new pending order and select that provider in checkout.
2. Check that the provider shows the frozen VND amount and its test environment.
3. Complete a test payment using its official sandbox test account/payment data.
4. Confirm the server callback arrives and the order becomes paid even if the
   browser return is not visited. Revisit order history to verify the result.
5. Check cancellation/expiry and use the status-check action with callback delivery
   temporarily unavailable. Confirm a retry does not create another active attempt.
6. Check your own merchant sandbox dashboard against the local payment reference.

No live merchant credentials or actual sandbox transactions are exercised by the
automated suite. Manual provider acceptance remains necessary after onboarding.

## Protocol references

- MoMo: [one-time wallet payment](https://developers.momo.vn/v3/vi/docs/payment/api/wallet/onetime/),
  [query](https://developers.momo.vn/v3/docs/payment/api/payment-api/query/),
  [IPN](https://developers.momo.vn/v3/vi/docs/payment/api/result-handling/notification/),
  [result codes](https://developers.momo.vn/v3/docs/payment/api/result-handling/resultcode/).
- ZaloPay: [create](https://docs.zalopay.vn/docs/specs/order-create/),
  [query](https://docs.zalopay.vn/docs/specs/order-query/),
  [callback](https://docs.zalopay.vn/docs/specs/callback-api/),
  [status codes](https://docs.zalopay.vn/docs/developer-tools/knowledge-base/status-codes/).
- VNPAY: [payment/IPN](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html)
  and [query protocol](https://sandbox.vnpayment.vn/apis/docs/truy-van-hoan-tien/querydr%26refund.html).
