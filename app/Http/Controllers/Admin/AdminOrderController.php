<?php

namespace App\Http\Controllers\Admin;

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
            'status' => ['nullable', Rule::in(['all', ...Order::STATUSES])],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? 'all';

        return view('admin.orders.index', [
            'orders' => $this->orders->forAdmin($keyword, $status),
            'keyword' => $keyword,
            'status' => $status,
            'counts' => $this->orders->statusCounts(),
            'statuses' => Order::STATUSES,
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
            'statuses' => Order::STATUSES,
        ]);
    }

    /**
     * UC-44: Update the order status (e.g. mark disputed, refunded, cancelled).
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Order::STATUSES)],
        ]);

        $this->orderService->updateStatus($order, $validated['status']);

        return back()->with('success', "Order #{$order->order_id} status updated to '{$order->status}'.");
    }
}
