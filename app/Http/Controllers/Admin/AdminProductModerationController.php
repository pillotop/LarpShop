<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProductModerationController extends Controller
{
    /**
     * UC-42: List vendor products by moderation status with search and vendor filter.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', ...Product::STATUSES])],
            'vendor_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? Product::STATUS_PENDING;
        $vendorId = $validated['vendor_id'] ?? null;

        $query = Product::query()
            ->with(['vendor', 'category', 'images'])
            ->latest('product_id');

        if ($keyword !== '') {
            $escaped = addcslashes($keyword, '\\%_');
            $query->where('name', 'like', "%{$escaped}%");
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($vendorId !== null) {
            $query->where('vendor_id', $vendorId);
        }

        $counts = Product::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
        $counts['all'] = array_sum($counts);

        return view('admin.products.moderation', [
            'products' => $query->paginate(15)->withQueryString(),
            'keyword' => $keyword,
            'status' => $status,
            'vendorId' => $vendorId,
            'vendors' => Vendor::query()->orderBy('store_name')->get(['vendor_id', 'store_name']),
            'counts' => $counts,
            'statuses' => Product::STATUSES,
        ]);
    }

    /**
     * UC-42: Approve a product so it appears in the public catalog.
     */
    public function approve(Product $product): RedirectResponse
    {
        $product->loadMissing('vendor');

        if (! $product->vendor || ! in_array($product->vendor->status, ['active', 'approved'], true)) {
            return back()->withErrors(['product' => "Cannot approve '{$product->name}': its store is not active."]);
        }

        $product->status = Product::STATUS_APPROVED;
        $product->save();

        return back()->with('success', "Product '{$product->name}' has been approved.");
    }

    /**
     * UC-42: Reject a product so it is hidden from the public catalog.
     */
    public function reject(Product $product): RedirectResponse
    {
        $product->status = Product::STATUS_REJECTED;
        $product->save();

        return back()->with('success', "Product '{$product->name}' has been rejected.");
    }
}
