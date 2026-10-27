<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Queries\Orders\OrderQuery;
use App\Services\Orders\OrderManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function __construct(
        private OrderQuery $orders,
        private OrderManagementService $orderService
    ) {}

    /**
     * UC-44: List all orders with status filter and search by order ID, username, or email.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', ...OrderStatus::values()])],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? 'all';

        return view('admin.orders.index', [
            'orders' => $this->orders->forAdmin($keyword, $status),
            'keyword' => $keyword,
            'status' => $status,
            'counts' => $this->orders->statusCounts(),
            'statuses' => OrderStatus::values(),
        ]);
    }

    /**
     * UC-44: Investigate a single order, including per-vendor breakdown and total reconciliation.
     */
    public function show(Order $order): View
    {
        $this->orders->loadInvestigationDetails($order);
        $reconciliation = $this->orderService->reconcile($order);

        return view('admin.orders.show', [
            'order' => $order,
            'computedTotal' => $reconciliation['computedTotal'],
            'totalMatches' => $reconciliation['totalMatches'],
            'byVendor' => $reconciliation['byVendor'],
            'canCancel' => $order->status === OrderStatus::Pending,
        ]);
    }

    /**
     * UC-44: Cancel an eligible order through the inventory-aware lifecycle.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([OrderStatus::Cancelled->value])],
        ]);

        $order = $this->orderService->updateStatus($order, OrderStatus::from($validated['status']));

        return back()->with('success', "Order #{$order->order_id} status updated to '{$order->status->value}'.");
    }
}
