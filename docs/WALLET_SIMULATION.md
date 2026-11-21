# Hosted wallet simulation

For a course demonstration without Vietnamese merchant sandbox accounts, set
`WALLET_SIMULATION_ENABLED=true` in the application environment. Rebuild the
Laravel configuration cache if used (`php artisan config:cache`). On Render,
save the variable and deploy the version containing this feature.

The wallet selector replaces MoMo, ZaloPay and VNPAY choices with clearly labeled
local simulations. PayPal and Stripe remain unchanged. The customer is redirected
to a LarpShop page to choose success, failure, cancellation or delayed success.
Delayed success takes 30 seconds; use the existing check/complete action afterward.
Leaving the page does not cancel or complete a payment.

These are not provider sandbox transactions. No provider credentials, webhook
listener, external exchange-rate lookup or card details are needed. The order's
authoritative amount and currency are preserved. Separate `momo_demo`,
`zalopay_demo` and `vnpay_demo` provider identifiers keep simulated attempts apart
from real integrations. No database schema change is required by this feature.

The simulator is disabled by default, supports hosted demonstrations in production
when explicitly enabled, and requires an authenticated payment owner and CSRF
protection. Existing payment and order lifecycle rules still apply, including
finalized-state protection and late-payment handling.

Set the flag to `false` after the demonstration. Real sandbox options then use
their own existing enable flags and credentials. Finish active demo attempts
before disabling the simulator; it cannot settle an existing real-provider attempt.
