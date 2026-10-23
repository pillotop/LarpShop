<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
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

        $query = Order::query()->with('user')->withCount('details')->latest('order_id');

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

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
        $counts['all'] = array_sum($counts);

        return view('admin.orders.index', [
            'orders' => $query->paginate(15)->withQueryString(),
            'keyword' => $keyword,
            'status' => $status,
            'counts' => $counts,
            'statuses' => Order::STATUSES,
        ]);
    }

    /**
     * UC-44: Investigate a single order, including per-vendor breakdown and total reconciliation.
     */
    public function show(Order $order): View
    {
        $order->load(['user', 'details.product.vendor.user']);

        $computedTotal = $order->details->sum(fn ($d) => $d->quantity * $d->unit_price);
        $totalMatches = abs((float) $order->total_amount - (float) $computedTotal) < 0.005;

        $byVendor = $order->details->groupBy(fn ($d) => $d->product?->vendor_id);

        return view('admin.orders.show', [
            'order' => $order,
            'computedTotal' => $computedTotal,
            'totalMatches' => $totalMatches,
            'byVendor' => $byVendor,
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

        $order->status = $validated['status'];
        $order->save();

        return back()->with('success', "Order #{$order->order_id} status updated to '{$order->status}'.");
    }
}
