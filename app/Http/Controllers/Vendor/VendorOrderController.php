<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateVendorOrderFulfillmentRequest;
use App\Models\Order;
use App\Models\Vendor;
use App\Queries\Vendors\VendorOrderQuery;
use App\Services\Vendors\VendorOrderFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorOrderController extends Controller
{
    public function __construct(
        private VendorOrderQuery $orders,
        private VendorOrderFulfillmentService $fulfillment,
    ) {}

    /**
     * UC-34: List orders containing products owned by the authenticated vendor.
     */
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'order_status' => ['nullable', Rule::in(['all', ...OrderStatus::values()])],
            'fulfillment_status' => ['nullable', Rule::in(['all', ...FulfillmentStatus::values()])],
        ]);

        $filters = [
            'q' => trim((string) ($validated['q'] ?? '')),
            'order_status' => $validated['order_status'] ?? 'all',
            'fulfillment_status' => $validated['fulfillment_status'] ?? 'all',
        ];

        $orders = $this->orders->forVendor($vendor, $filters);

        $subtotals = [];
        foreach ($orders as $order) {
            $subtotals[$order->order_id] = $this->orders->calculateVendorSubtotal($order);
        }

        return view('vendor.orders.index', [
            'vendor' => $vendor,
            'orders' => $orders,
            'subtotals' => $subtotals,
            'summary' => $this->orders->summaryForVendor($vendor),
            'filters' => $filters,
            'orderStatuses' => OrderStatus::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
        ]);
    }

    /**
     * UC-34: Display vendor-scoped details and shipping address for a specific order.
     */
    public function show(Request $request, Order $order): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $vendorOrder = $this->orders->findOrderForVendor($vendor, $order->order_id);

        return view('vendor.orders.show', [
            'vendor' => $vendor,
            'order' => $vendorOrder,
            'vendorSubtotal' => $this->orders->calculateVendorSubtotal($vendorOrder),
            'shippingAddress' => $vendorOrder->addresses->firstWhere('type', 'shipping') ?? $vendorOrder->addresses->first(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
        ]);
    }

    /**
     * UC-35: Update fulfillment & shipping status for the vendor's items in an order.
     */
    public function updateFulfillment(UpdateVendorOrderFulfillmentRequest $request, Order $order): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->vendor();

        $validated = $request->validated();
        $target = FulfillmentStatus::from($validated['fulfillment_status']);
        $detailId = isset($validated['order_detail_id']) ? (int) $validated['order_detail_id'] : null;

        $this->fulfillment->updateFulfillment($vendor, $order, $target, $detailId);

        return redirect()
            ->route('vendor.orders.show', $order->order_id)
            ->with('success', "Fulfillment status updated to '{$target->label()}' successfully.");
    }
}
