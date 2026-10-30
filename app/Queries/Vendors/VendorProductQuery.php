<?php

namespace App\Queries\Vendors;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorProductQuery
{
    public const LOW_STOCK_THRESHOLD = 5;

    public const AVAILABILITY_FILTERS = [
        'all' => 'All products',
        'visible' => 'Visible in catalog',
        'hidden' => 'Hidden by store',
        'in_stock' => 'In stock',
        'low_stock' => 'Low stock (<= 5)',
        'out_of_stock' => 'Out of stock (0)',
    ];

    /**
     * Paginated list of products belonging exclusively to the given vendor.
     *
     * @param  array{q?: string|null, category_id?: int|null, availability?: string|null, status?: string|null}  $filters
     */
    public function forVendor(Vendor $vendor, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = $vendor->products()
            ->with([
                'category',
                'images' => fn ($q) => $q->orderBy('sort_order')->orderBy('product_image_id'),
            ]);

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $escaped = addcslashes($keyword, '\\%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('slug', 'like', "%{$escaped}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        $availability = $filters['availability'] ?? 'all';
        match ($availability) {
            'visible' => $query->where('is_active', true),
            'hidden' => $query->where('is_active', false),
            'in_stock' => $query->where('stock', '>', 0),
            'low_stock' => $query->whereBetween('stock', [1, self::LOW_STOCK_THRESHOLD]),
            'out_of_stock' => $query->where('stock', 0),
            default => null,
        };

        $status = $filters['status'] ?? 'all';
        if ($status !== 'all' && in_array($status, Product::STATUSES, true)) {
            $query->where('status', $status);
        }

        return $query
            ->latest('product_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Inventory & visibility summary counts for the vendor's catalog (UC-33).
     *
     * @return array{total: int, visible: int, hidden: int, in_stock: int, low_stock: int, out_of_stock: int}
     */
    public function inventorySummary(Vendor $vendor): array
    {
        $base = fn () => $vendor->products();

        return [
            'total' => $base()->count(),
            'visible' => $base()->where('is_active', true)->count(),
            'hidden' => $base()->where('is_active', false)->count(),
            'in_stock' => $base()->where('stock', '>', 0)->count(),
            'low_stock' => $base()->whereBetween('stock', [1, self::LOW_STOCK_THRESHOLD])->count(),
            'out_of_stock' => $base()->where('stock', 0)->count(),
        ];
    }

    /**
     * Resolve a product owned by the given vendor, optionally locking the row for update.
     */
    public function findOwned(Vendor $vendor, int $productId, bool $lockForUpdate = false): Product
    {
        $query = $vendor->products()->whereKey($productId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }
}
