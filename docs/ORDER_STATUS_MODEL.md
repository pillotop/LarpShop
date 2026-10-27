# Order status after the checkout/admin merge

## State ownership

`OrderStatus` is the single allowlist for `orders.status`. `Order::STATUSES` was removed
because it mixed order lifecycle, payment results, delivery, and disputes.

| Domain | Values currently supported | Owner |
| --- | --- | --- |
| Order lifecycle | `pending`, `paid`, `cancelled`, `expired` | Order lifecycle and payment services |
| Payment attempts | `pending`, `processing`, `succeeded`, `failed`, `cancelled`, `refunded`, `refund_required`, `delayed` | Payment services and gateways |
| Delivery | No workflow implemented yet | Future delivery/fulfillment feature |
| Disputes | No workflow implemented yet | Future investigation/dispute feature |

`paid` is the current order-level confirmation milestone, set after a verified successful
payment. The payment record still owns provider outcome, refunds, and individual attempts.
A failed attempt leaves an otherwise payable order pending. A payment received after
cancellation or expiration requires refund review; it must not reopen the order.

Delivery must be modeled separately when implemented, especially because an order can
contain products from multiple vendors. Do not overwrite payment or order lifecycle with
`shipped`/`delivered`. A refund label is not a substitute for actually refunding a payment.

## Administrative actions

- Order listing/filtering uses `OrderStatus::values()`.
- Investigation shows the order lifecycle and each payment attempt separately.
- The current administrative mutation is cancellation of an eligible pending order.
- `OrderManagementService` delegates to `OrderLifecycleService`, preserving row locking,
  stock restoration, and idempotency. It never directly assigns a payment outcome.
- Marking an order `paid`, `refunded`, `disputed`, or a delivery state through the status
  endpoint is rejected. Future financial and fulfillment actions need their own workflows.

## Reports and sample data

Revenue and product/vendor rankings require a `succeeded` payment, rather than assuming
that a delivery label proves payment. Existence checks prevent payment attempts from
multiplying order totals or line quantities. Refunded/failed/processing attempts do not
establish revenue. Paid and cancelled/expired rates replace the unimplemented delivery metric.

Sample orders use supported lifecycle values. Pending samples reserve their quantities
and have an expiry; paid samples have a matching successful demonstration payment record.
No existing development orders were rewritten: read-only inspection found only `pending`.
Other databases containing old delivery/dispute labels require a deliberate data migration;
do not silently reinterpret such labels as evidence of payment.

## Verification and merge state

Regression tests cover admin cancellation, exact-once stock restoration, payment-only
revenue, enum-compatible views, seed data, checkout, and existing payment flows.
The repository PHPUnit configuration uses MySQL `larpshop_testing`, with forced testing
environment and database settings. Connection credentials come from the local environment.
The earlier SQLite configuration could not execute the MySQL-only review CHECK migration.

Editing a conflict's file content does not stage its Git resolution. The merge index is
left for the developer to review and stage.

## Changed files

- `app/Models/Order.php`
- `app/Models/OrderDetail.php`
- `app/Enums/OrderStatus.php`
- `app/Http/Controllers/Admin/AdminOrderController.php`
- `app/Http/Controllers/Admin/AdminReportController.php`
- `app/Services/Orders/OrderManagementService.php`
- `app/Queries/Orders/OrderQuery.php`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/reports/index.blade.php`
- `resources/views/components/admin/order-table.blade.php`
- `resources/views/components/admin/order-summary-cards.blade.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/AdminOrderTest.php`
- `tests/Feature/AdminReportTest.php`
- `tests/Feature/OrderStatusConsistencyTest.php`
- `docs/ORDER_STATUS_MODEL.md`
