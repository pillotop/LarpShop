<?php

namespace App\Queries\Orders;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderQuery
{
    /**
     * UC-44: Paginated order list for the admin console with status filter and search.
     */
    public function forAdmin(?string $keyword = null, string $status = 'all', int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()->with('user')->withCount('details')->latest('order_id');

        if ($keyword !== null && $keyword !== '') {
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

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * UC-44: Status counts for order tabs.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $counts['all'] = array_sum($counts);

        return $counts;
    }

    /**
     * UC-44: Eager-load relations required for order investigation.
     */
    public function loadInvestigationDetails(Order $order): Order
    {
        return $order->loadMissing(['user', 'details.product.vendor.user', 'payments']);
    }
}
