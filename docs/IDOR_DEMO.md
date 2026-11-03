# Order detail IDOR demonstration

This deliberately vulnerable change belongs only on the `intentionally-vuln`
branch for local security demonstrations with disposable test accounts.

## Affected endpoint

`GET /orders/{order}` (`orders.show`), handled by `OrderController::show()`.

Authentication still applies. Laravel resolves `{order}` globally by `order_id`,
but the demonstration removes the ownership-scoped lookup before rendering or
returning that order. Any signed-in customer who knows another customer's order
ID can read its detail page or JSON representation.

The cancellation endpoint retains its ownership check. No payment, inventory,
address-editing, or cart authorization was weakened.

## Local reproduction

1. Use two local test accounts, A and B, with an order belonging to B.
2. Sign in as A and open `/orders/<B-order-id>` on the local application.
3. Observe HTTP 200 and B's order details instead of the protected HTTP 404.
4. For a smaller response, request the same endpoint with
   `Accept: application/json` using A's authenticated session.

Use an existing test order ID rather than assuming that IDs start at 1. The
order-history list still shows only A's orders; this demonstrates direct access
by changing an object reference, not an intentionally broadened history query.

## Existing regression test

Run against the isolated test database configured by `phpunit.xml`:

```bash
php artisan test --filter=test_cancel_is_idempotent_and_redirects_to_an_owned_get_endpoint
```

Its cross-user GET assertion should fail: it expects 404 but this demonstration
returns 200. Keep that security assertion unchanged; the failure detects the
intentional vulnerability and must not be presented as a passing secure suite.

## Remediation

Restore this line at the beginning of `OrderController::show()`:

```php
$order = $request->user()->orders()->findOrFail($order->getKey());
```

Alternatively enforce an equivalent ownership policy before loading or returning
the order. Retest with both the owner and a different customer.
