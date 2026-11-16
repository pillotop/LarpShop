<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Queries\Vendors\VendorQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorVoucherController extends Controller
{
    public function __construct(
        private VendorQuery $vendors,
    ) {}

    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $vouchers = Voucher::query()
            ->where('vendor_id', $vendor->vendor_id)
            ->latest('voucher_id')
            ->paginate(15);

        return view('vendor.vouchers.index', [
            'vendor' => $vendor,
            'ownedVendors' => $request->attributes->get('owned_active_vendors') ?? $this->vendors->activeForUser($request->user()),
            'vouchers' => $vouchers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'alpha_dash', 'min:3', 'max:40', 'unique:vouchers,code'],
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in([Voucher::TYPE_PERCENT, Voucher::TYPE_FIXED])],
            'value' => ['required', 'numeric', 'min:0.01', $request->input('type') === Voucher::TYPE_PERCENT ? 'max:100' : 'max:99999.99'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999.99'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        Voucher::create([
            'vendor_id' => $vendor->vendor_id,
            'code' => $validated['code'],
            'title' => trim($validated['title']),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_user_limit' => $validated['per_user_limit'] ?? 1,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('vendor.vouchers.index')
            ->with('success', __('management.vouchers.created', ['code' => $validated['code']]));
    }

    public function toggle(Request $request, Voucher $voucher): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();
        abort_unless((int) $voucher->vendor_id === (int) $vendor->vendor_id, 403);

        $voucher->update(['is_active' => ! $voucher->is_active]);

        return redirect()->route('vendor.vouchers.index')
            ->with('success', __('management.vouchers.toggled', ['code' => $voucher->code]));
    }

    public function destroy(Request $request, Voucher $voucher): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();
        abort_unless((int) $voucher->vendor_id === (int) $vendor->vendor_id, 403);

        $code = $voucher->code;
        $voucher->delete();

        return redirect()->route('vendor.vouchers.index')
            ->with('success', __('management.vouchers.deleted', ['code' => $code]));
    }
}
