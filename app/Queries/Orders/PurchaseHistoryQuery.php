<?php

namespace App\Queries\Orders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Builds the signed-in customer's purchase history shown in the profile page.
 *
 * Every filter value is validated against an explicit allowlist, and the base
 * query is always scoped to the given user's own orders.
 */
class PurchaseHistoryQuery
{
    public const FILTERS = [
        'all',
        'unpaid',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
        'feedback',
    ];

    /** Period key => number of days to look back (null = no limit). */
    public const PERIODS = [
        'all' => null,
        '30d' => 30,
        '90d' => 90,
        '1y' => 365,
    ];

    public const PER_PAGE = 5;

    public const MAX_KEYWORD_LENGTH = 100;

    public const PAGE_NAME = 'orders_page';

    public const FRAGMENT = 'pane-purchases';

    /**
     * Normalizes raw query-string values into a safe filter set.
     *
     * Unknown or malformed values silently fall back to defaults so a bad URL
     * never breaks the profile page.
     *
     * @param  array<string, mixed>  $input
     * @return array{q: string, filter: string, period: string}
     */
    public function normalizeFilters(array $input): array
    {
        $keyword = $input['orders_q'] ?? '';
        $keyword = is_string($keyword)
            ? mb_substr(trim($keyword), 0, self::MAX_KEYWORD_LENGTH)
            : '';

        $filter = $input['orders_filter'] ?? 'all';
        $filter = is_string($filter) && in_array($filter, self::FILTERS, true) ? $filter : 'all';

        $period = $input['orders_period'] ?? 'all';
        $period = is_string($period) && array_key_exists($period, self::PERIODS) ? $period : 'all';

        return ['q' => $keyword, 'filter' => $filter, 'period' => $period];
    }

    /**
     * @param  array{q: string, filter: string, period: string}  $filters
     */
    public function forUser(User $user, array $filters): LengthAwarePaginator
    {
        $query = $user->orders()
            ->with([
                'details.product.images',
                'details.product.vendor',
                'payments',
            ]);

        $this->applyKeyword($query, $filters['q']);
        $this->applyPeriod($query, $filters['period']);
        $this->applyStatusFilter($query, $filters['filter'], $user);

        return $query
            ->latest('orders.created_at')
            ->orderByDesc('orders.order_id')
            ->paginate(self::PER_PAGE, ['orders.*'], self::PAGE_NAME)
            ->withQueryString()
            ->fragment(self::FRAGMENT);
    }

    private function applyKeyword(Builder|Relation $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $orderId = ltrim($keyword, '#');

        $query->where(function ($search) use ($keyword, $orderId) {
            // Order numbers are plain integers; ignore absurdly long digit strings.
            if (ctype_digit($orderId) && strlen($orderId) <= 12) {
                $search->orWhere('orders.order_id', (int) $orderId);
            }

            $search->orWhereHas('details.product', fn ($products) => $products
                ->where('products.name', 'like', '%'.$keyword.'%'));
        });
    }

    private function applyPeriod(Builder|Relation $query, string $period): void
    {
        $days = self::PERIODS[$period] ?? null;

        if ($days !== null) {
            $query->where('orders.created_at', '>=', now()->subDays($days));
        }
    }

    private function applyStatusFilter(Builder|Relation $query, string $filter, User $user): void
    {
        match ($filter) {
            'unpaid' => $query->where('orders.status', OrderStatus::Pending->value),
            'cancelled' => $query->whereIn('orders.status', [
                OrderStatus::Cancelled->value,
                OrderStatus::Expired->value,
            ]),
            'processing' => $this->paidWithFulfillment($query, [
                FulfillmentStatus::Unfulfilled,
                FulfillmentStatus::Processing,
            ]),
            'shipped' => $this->paidWithFulfillment($query, [FulfillmentStatus::Shipped]),
            'delivered' => $this->paidWithFulfillment($query, [FulfillmentStatus::Delivered]),
            'feedback' => $query
                ->where('orders.status', OrderStatus::Paid->value)
                ->whereHas('details', fn ($details) => $details->whereHas('product', fn ($products) => $products
                    ->where('products.status', Product::STATUS_APPROVED)
                    ->whereDoesntHave('reviews', fn ($reviews) => $reviews
                        ->where('user_id', $user->getKey())))),
            default => null,
        };
    }

    /**
     * @param  list<FulfillmentStatus>  $statuses
     */
    private function paidWithFulfillment(Builder|Relation $query, array $statuses): void
    {
        $query
            ->where('orders.status', OrderStatus::Paid->value)
            ->whereHas('details', fn ($details) => $details->whereIn(
                'order_details.fulfillment_status',
                array_map(fn (FulfillmentStatus $status) => $status->value, $statuses),
            ));
    }
}
