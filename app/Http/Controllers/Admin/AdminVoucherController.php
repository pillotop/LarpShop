<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminVoucherController extends Controller
{
    public function index(): View
    {
        $vouchers = Voucher::query()
            ->with('vendor')
            ->latest('voucher_id')
            ->paginate(15);

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'alpha_dash', 'min:3', 'max:40', 'unique:vouchers,code'],
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(Voucher::TYPES)],
            'value' => [
                Rule::requiredIf(fn () => $request->input('type') !== Voucher::TYPE_FREE_SHIPPING),
                'nullable',
                'numeric',
                'min:0',
                $request->input('type') === Voucher::TYPE_PERCENT ? 'max:100' : 'max:99999.99',
            ],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999.99'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        Voucher::create([
            'vendor_id' => null,
            'code' => $validated['code'],
            'title' => trim($validated['title']),
            'type' => $validated['type'],
            'value' => $validated['type'] === Voucher::TYPE_FREE_SHIPPING ? 0 : ($validated['value'] ?? 0),
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_user_limit' => $validated['per_user_limit'] ?? 1,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('admin.vouchers.index')
            ->with('success', __('management.vouchers.created', ['code' => $validated['code']]));
    }

    public function toggle(Voucher $voucher): RedirectResponse
    {
        $voucher->update(['is_active' => ! $voucher->is_active]);

        return redirect()->route('admin.vouchers.index')
            ->with('success', __('management.vouchers.toggled', ['code' => $voucher->code]));
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $code = $voucher->code;
        $voucher->delete();

        return redirect()->route('admin.vouchers.index')
            ->with('success', __('management.vouchers.deleted', ['code' => $code]));
    }
}
