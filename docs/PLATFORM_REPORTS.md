# Platform reports

The administrator report is available at `admin.reports.index`. Existing admin middleware protects access; the report only reads data.

## Stage 1: Reporting definitions

### Period

`ReportPeriod` captures one cutoff using the application timezone. `7_days` and `30_days` are rolling periods ending at that cutoff; `this_month` starts at the beginning of the current month. `all` has no lower bound. Both boundaries are inclusive. Future-dated records do not contribute to period metrics.

### Completed sales

An eligible sale is an order whose current status is `paid`, supported by a payment whose current status is `succeeded` and whose `completed_at` is present. Each order contributes once, using the earliest dated successful payment; ties use the smallest `payment_id`. Selection happens before applying date and source filters, so duplicate successful payments cannot repeat a sale in another period or source.

- Completed sales value: sum of eligible `orders.total_amount`.
- Orders paid in this period: number of eligible orders, selected by payment completion date.
- Average paid order value: completed sales value divided by those orders, or zero when empty.
- Product and vendor rankings: quantities and `unit_price` from order details for the same eligible orders. Product names and vendor ownership come from their current records.
- Monetary values use the catalog/order base currency (`currency.base`, currently USD). Payment settlement amounts are not summed or converted at reporting time. A VND settlement does not change an order's original USD value. Customer display-currency preferences do not change reports.
- Successful payment records without a completion date are excluded and counted separately, across all sources and time. No historical dates are guessed or backfilled.
- Failed, pending, refunded, and refund-required payments do not qualify. These are operational sales figures based on current states, not an immutable accounting ledger or a report of gross receipts minus refund transactions.

The sales source selector applies to completed sales, their comparisons and trends, and product/vendor rankings. Created-order counts and payment-attempt statistics always include all sources:

- `all`: provider payments and simulations.
- `simulated`: providers marked `simulated` in payment configuration, including `fake` and the dedicated wallet demo providers.
- `provider`: the remaining providers, regardless of whether currently enabled.

Provider payments must not be described as live revenue. Payments do not store their historical sandbox/live mode. Current credentials or endpoint configuration cannot establish which environment a historical payment used. Provider keys and their simulation classification should remain stable for historical reporting.

### Orders created in the period

Order counts and the donut chart use `orders.created_at` and current order statuses. They include all payment sources, regardless of the sales source selector. The paid share and cancelled/expired share describe this cohort of orders; neither is a payment-attempt success rate. An order created outside the period but paid inside it can contribute to sales without contributing to this cohort.

Chart segments and accessible status links open the admin order list filtered by status across all time. That list currently does not support a date filter.

### Current platform snapshot

Active vendors use the existing `Vendor::active()` scope. Approved products and category product counts describe current records. These figures do not depend on the period or sales source selector. Category totals include directly assigned approved products, not recursive descendant totals.

## Responsibilities

- `AdminReportController`: validate allowlisted filters and pass view data.
- `ReportPeriod`: resolve period boundaries with one fixed cutoff.
- `PlatformReportQuery`: filter, aggregate, and rank data in the database.
- `CompletedSaleQuery`: provide the shared, deduplicated sales query and monetary summary.
- `PlatformTrendQuery`: aggregate sales and order creation by date/month and fill empty buckets.
- `PaymentReportQuery`: count payment attempts by provider and current status without summing settlement amounts.
- `InventoryReportQuery`: reuse catalog visibility and report available stock and moderation work.
- `FulfillmentReportQuery`: count paid-order lines and units and list the current dispatch queue.
- `VendorApplicationReportQuery`: report latest submissions, valid review durations, and pending applications.
- `ReviewReportQuery`: aggregate surviving reviews by rating and list recent low ratings.
- `admin/reports/partials`: present filters and metric groups.
- `components/admin/order-status-chart` and its page script: render the interactive chart with accessible links and textual values.
- `admin/report-charts.js`: render line and stacked bar charts with Chart.js, honoring the current theme and reduced-motion preference on load. Text tables remain available without JavaScript.

## Stage 2: Trends and payment activity

### Period comparison

Bounded periods compare with the immediately preceding interval of equal elapsed duration. The previous interval includes its start and excludes its end, which is the current interval's start. The UI shows the exact dates and timezone. For `this_month`, this is an equal-duration preceding interval, not a full previous calendar month. All-time and zero-length periods have no comparison.

Sales value, paid-order count, and average paid-order value use the same sales source in both periods. Created-order counts always include all sources. Percentage change is `(current - previous) / previous * 100`, rounded to one decimal. A zero previous value has no percentage baseline unless the current value is also zero, in which case change is zero percent. No infinite or arbitrary 100% growth is invented.

### Trends

Bounded periods have daily buckets. Rolling periods can include partial days at both ends, as indicated in the UI. All-time history has monthly buckets beginning at the earliest eligible sale or order creation. Missing buckets are filled with zero. An empty all-time report contains the current month with zeros. No full orders or payments are loaded into PHP to construct the series; only database aggregates are loaded.

The sales chart uses base-currency order totals and payment completion dates. The order chart distinguishes paid orders (completion date, selected sales source) from newly created orders (creation date, all sources). Both use the same definitions and cutoff as the summary cards. Chart legends support hiding/showing series, tooltips expose values, and a native expandable table supplies a text alternative.

### Payment-attempt cohort

The provider report selects payment records by their own `created_at` within the reporting period, across all sources. It counts each retry and duplicate record separately, while the sales query still counts an order once. It shows the number of distinct orders across all attempts, not the sum of each provider's order count.

States are grouped as follows:

- Succeeded: `succeeded`.
- Failed: `failed`.
- In progress: `pending`, `processing`, `delayed`.
- Cancelled: `cancelled`.
- Refund-related: `refunded`, `refund_required`.
- Other: unrecognized statuses, so totals still reconcile.

The success rate is currently succeeded attempts divided by all attempts, including in-progress attempts. A successful record with no completion date still contributes to this status-based rate, but cannot contribute to dated sales. This is not an event history of status transitions or a checkout conversion rate. Old attempts completed in the current period can contribute to current sales without belonging to the current attempt cohort.

Provider names and simulation labels come from payment configuration, including disabled providers with historical records; unknown provider names are escaped. A stacked bar chart and an always-visible table show the same counts. Zero denominators yield zero rates. No payment API requests are made by reports.

## Stage 3: Operations

All operation sections include every payment source. The sales source filter does not apply. They use the same report cutoff and existing admin routes; reports do not change operational statuses.

### Available inventory

The inventory snapshot reuses `ProductCatalogQuery::constrainCollectionVisibility`: approved, vendor-enabled products in active stores. Products created after the cutoff are excluded. It includes old products regardless of the selected period. Product counts are disjoint: zero stock, 1–5 units, and more than five units. The available-unit total is the sum of their current `stock` values; checkout reservations have already reduced those values. This is not physical warehouse inventory.

The attention list contains at most five products with at most five units, sorted by stock then product ID. Product links open the public detail page; store links open the existing approved-product moderation list scoped to that vendor. The separate pending-moderation count includes every currently pending product up to the cutoff, not only visible products. The report does not change approval, stock, or visibility rules.

### Fulfillment

The period cohort is order lines from orders currently marked `paid`, selected by **order creation date**, across all sources. This matches the existing vendor fulfillment workflow; it deliberately does not require the dated payment evidence used by sales reports. Thus a legacy paid order can require fulfillment while being excluded from sales metrics.

Count distinct orders, individual order lines, and quantity units separately. A multi-vendor order contributes one order and multiple lines. Repeated payment records do not multiply counts. Current fulfillment states are grouped using `FulfillmentStatus`; unknown states are shown as other. These are application-maintained states, not carrier events or shipment counts.

The separate all-time dispatch queue includes unfulfilled and processing lines in currently paid orders up to the cutoff. Its five oldest rows are sorted by order creation date and line ID and link to admin order investigation. Dates shown are order creation dates, not the start of a shipping SLA or time since payment. Shipped, delivered, pending-payment, cancelled, and expired orders/lines do not enter this queue.

### Vendor applications

The period cohort uses the latest stored `submitted_at` and current submitted/approved/rejected status. Drafts, undated legacy applications, and future submissions are excluded. Resubmission overwrites the prior submission date, so these figures cannot describe every historical submission or review decision.

Average review time in hours uses only currently approved/rejected applications in that cohort with non-null review dates, review dates at or after submission, and review dates no later than the cutoff. The sample size is displayed. A submitted application's stale review date from an earlier rejection is never treated as a current decision. Missing or inconsistent dates do not receive guessed durations; no samples means an unavailable average, not zero hours.

The current pending count and five oldest pending applications include all dated submissions up to the cutoff, independent of the period. Linked applications open the existing vendor investigation page; records without a linked vendor are displayed without an invalid link. Legacy pending vendors without an application are counted separately and link to the pending vendor list.

### Product reviews

Reviews are selected by their creation date in the period, independent of the sales source. Counts, average rating, and 1–5 star distribution reflect rows currently stored. Deleted reviews no longer contribute, so this is not a moderation/deletion history. No review eligibility, verified-purchase rate, or review-to-purchase conversion is inferred.

The report shows the five newest 1–2 star reviews in the cohort. Product names and comment excerpts are escaped. Rating links open the existing admin review list for that exact rating across all time; the moderation list does not support a reporting-period filter. Empty cohorts show zero counts and no invented average rating.

## Next stages

1. Stage 1: definitions, query extraction, explicit reporting scopes, and regression tests — implemented.
2. Stage 2: daily sales/order trends, comparison with the preceding period, and provider/payment-attempt reporting — implemented.
3. Stage 3: inventory, fulfillment, vendor applications, and review reporting — implemented.

No schema changes or historical data rewrites are required for these stages. Future support for changing the catalog base currency requires an order currency snapshot; changing a configuration value cannot reinterpret old order amounts safely.
