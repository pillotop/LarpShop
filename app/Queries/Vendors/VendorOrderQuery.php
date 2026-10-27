<?php

namespace App\Queries\Vendors;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Vendor;
use App\Support\Money\MoneyCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorOrderQuery
{
    public function __construct(
        private MoneyCalculator $money,
    ) {}

    /**
     * UC-34: Paginated list of orders containing at least one product owned by the given vendor.
     * Eager loads only the order_details belonging to this vendor so other vendors' items remain isolated.
     *
     * @param  array{q?: string|null, order_status?: string|null, fulfillment_status?: string|null}  $filters
     */
    public function forVendor(Vendor $vendor, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $vendorId = (int) $vendor->vendor_id;

        $query = Order::query()
            ->whereHas('details.product', fn ($q) => $q->where('vendor_id', $vendorId))
            ->with([
                'user',
                'addresses',
                'details' => fn ($q) => $q
                    ->whereHas('product', fn ($p) => $p->where('vendor_id', $vendorId))
                    ->with(['product.images'])
                    ->orderBy('order_detail_id'),
            ]);

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $escaped = addcslashes(ltrim($keyword, '#'), '\\%_');
            $query->where(function ($q) use ($escaped) {
                $q->whereHas('user', function ($u) use ($escaped) {
                    $u->where('username', 'like', "%{$escaped}%")
                        ->orWhere('email', 'like', "%{$escaped}%");
                });

                if (ctype_digit($escaped)) {
                    $q->orWhere('order_id', (int) $escaped);
                }
            });
        }

        $orderStatus = $filters['order_status'] ?? 'all';
        if ($orderStatus !== 'all' && in_array($orderStatus, OrderStatus::values(), true)) {
            $query->where('status', $orderStatus);
        }

        $fulfillmentStatus = $filters['fulfillment_status'] ?? 'all';
        if ($fulfillmentStatus !== 'all' && in_array($fulfillmentStatus, FulfillmentStatus::values(), true)) {
            $query->whereHas('details', function ($q) use ($vendorId, $fulfillmentStatus) {
                $q->where('fulfillment_status', $fulfillmentStatus)
                    ->whereHas('product', fn ($p) => $p->where('vendor_id', $vendorId));
            });
        }

        return $query
            ->latest('order_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * UC-34: Resolve a single order containing items owned by the given vendor.
     * Eager loads only this vendor's order_details and shipping address.
     */
    public function findOrderForVendor(Vendor $vendor, int $orderId): Order
    {
        $vendorId = (int) $vendor->vendor_id;

        return Order::query()
            ->whereKey($orderId)
            ->whereHas('details.product', fn ($q) => $q->where('vendor_id', $vendorId))
            ->with([
                'user',
                'addresses',
                'details' => fn ($q) => $q
                    ->whereHas('product', fn ($p) => $p->where('vendor_id', $vendorId))
                    ->with(['product.images', 'product.category'])
                    ->orderBy('order_detail_id'),
            ])
            ->firstOrFail();
    }

    /**
     * Calculate the subtotal for the vendor-scoped order details loaded on the given order.
     */
    public function calculateVendorSubtotal(Order $order): string
    {
        $total = $this->money->zero();

        foreach ($order->details as $detail) {
            $total = $this->money->add(
                $total,
                $this->money->subtotal((string) $detail->unit_price, (int) $detail->quantity),
            );
        }

        return $this->money->format($total);
    }

    /**
     * UC-34 & UC-35: Summary counts of orders and fulfillment items for the vendor.
     *
     * @return array{total_orders: int, paid_orders: int, unfulfilled: int, processing: int, shipped: int, delivered: int}
     */
    public function summaryForVendor(Vendor $vendor): array
    {
        $vendorId = (int) $vendor->vendor_id;

        $ordersBase = fn () => Order::query()
            ->whereHas('details.product', fn ($q) => $q->where('vendor_id', $vendorId));

        $itemsBase = fn () => OrderDetail::query()
            ->whereHas('product', fn ($q) => $q->where('vendor_id', $vendorId))
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid->value));

        $statusCounts = $itemsBase()
            ->selectRaw('fulfillment_status, COUNT(*) as total')
            ->groupBy('fulfillment_status')
            ->pluck('total', 'fulfillment_status')
            ->all();

        return [
            'total_orders' => $ordersBase()->count(),
            'paid_orders' => $ordersBase()->where('status', OrderStatus::Paid->value)->count(),
            'unfulfilled' => (int) ($statusCounts[FulfillmentStatus::Unfulfilled->value] ?? 0),
            'processing' => (int) ($statusCounts[FulfillmentStatus::Processing->value] ?? 0),
            'shipped' => (int) ($statusCounts[FulfillmentStatus::Shipped->value] ?? 0),
            'delivered' => (int) ($statusCounts[FulfillmentStatus::Delivered->value] ?? 0),
        ];
    }
}
