# Cart negative-quantity business logic demonstration

This deliberately vulnerable change belongs only on the `intentionally-vuln`
branch for local security demonstrations with disposable test accounts.

## Affected endpoints and root cause

- `POST /cart/add` (`cart.add`), handled by `CartController::add()`
- `POST /cart` (`cart.store`), handled by `CartController::store()`
- Service layer: `CartMutationService::updateQuantityLocked()`

In the secure implementation, `CartController::writeQuantity()` enforces
`min:1` when adding items (`cart.add`) and `min:0` when setting an item's
quantity (`cart.store`), while `CartMutationService::updateQuantityLocked()`
enforces a second guard rejecting `$quantity < 0`.

This demonstration removes both lower-bound checks. Because `cart_items.quantity`
is a signed integer column and `CartRetrievalService::getCartData()` computes
each line item's subtotal as `$cartItem->quantity * $product->price`, a negative
quantity produces a negative line subtotal that reduces `cart.total_amount`
(or makes the cart total negative).

`OrderCreationService::createLocked()` retains its checkout guard (`$item->quantity < 1`),
so order creation and payment processing are not weakened.

## Local reproduction

1. Sign in to the local application (or use a guest session) and add Product A
   with a positive quantity (for example `quantity=2` at `$50.00`, giving a
   `$100.00` cart total).
2. Submit a `POST /cart` or `POST /cart/add` request for Product B with a
   negative quantity (for example `quantity=-1` at `$40.00`).
3. Inspect the JSON response or reload `/cart/detail`:
   - Product B is stored in the cart with `quantity: -1` and a negative subtotal
     (`-40.00`).
   - `cart.total_amount` drops from `100.00` to `60.00`.
4. Clear the disposable cart items by setting `quantity=0` after completing the
   demonstration.

## Existing regression test

Run against the isolated test database configured by `phpunit.xml`:

```bash
php artisan test --filter=test_invalid_quantities_do_not_create_items
```

The `negative add` (`cart.add` with `-1`) and `negative set` (`cart.store` with
`-1`) dataset cases expect HTTP 422 validation errors and zero `cart_items`
records. On this branch, those assertions fail because the negative quantity is
accepted and persisted. Keep the test assertions unchanged so the test suite
flags the flaw.

## Remediation

1. Restore the lower-bound rule in `CartController::writeQuantity()`:

```php
$validated = $request->validate([
    'product_id' => ['required', 'integer', Rule::exists('products', 'product_id')],
    'quantity' => ['required', 'integer', $add ? 'min:1' : 'min:0', 'max:2147483647'],
]);
```

2. Restore the domain guard at the start of `CartMutationService::updateQuantityLocked()`:

```php
if ($quantity < 0) {
    throw ValidationException::withMessages([
        'quantity' => 'Quantity cannot be negative.',
    ]);
}
```

Rerun `CartTest` after restoring both checks.

## Changed files

- `app/Http/Controllers/CartController.php`
- `app/Services/Cart/CartMutationService.php`
- `docs/BUSINESS_LOGIC_DEMO.md`
