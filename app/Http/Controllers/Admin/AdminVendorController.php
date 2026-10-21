<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminVendorController extends Controller
{
    /**
     * UC-40: List vendors and applications with status filtering.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $query = Vendor::with('user')->latest();

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'active', 'rejected', 'suspended'])) {
            if ($status === 'approved') {
                $query->whereIn('status', ['approved', 'active']);
            } else {
                $query->where('status', $status);
            }
        }

        $vendors = $query->paginate(12)->withQueryString();

        $counts = [
            'all' => Vendor::count(),
            'pending' => Vendor::where('status', 'pending')->count(),
            'approved' => Vendor::whereIn('status', ['approved', 'active'])->count(),
            'rejected' => Vendor::where('status', 'rejected')->count(),
            'suspended' => Vendor::where('status', 'suspended')->count(),
        ];

        return view('admin.vendors.index', compact('vendors', 'status', 'counts'));
    }

    /**
     * UC-40: Approve a pending vendor application.
     */
    public function approve(Vendor $vendor): RedirectResponse
    {
        $vendor->status = 'approved';
        $vendor->save();

        if ($vendor->user) {
            $vendor->user->role = 'vendor';
            $vendor->user->save();
        }

        return back()->with('success', "Đã phê duyệt thành công gian hàng '{$vendor->store_name}'. Tài khoản người dùng đã được nâng cấp lên Người bán (Vendor).");
    }

    /**
     * UC-40: Reject a pending vendor application.
     */
    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->status = 'rejected';
        $vendor->save();

        if ($vendor->user && $vendor->user->role === 'vendor') {
            $vendor->user->role = 'customer';
            $vendor->user->save();
        }

        return back()->with('warning', "Đã từ chối đơn đăng ký gian hàng '{$vendor->store_name}'.");
    }

    /**
     * UC-40: Suspend an active vendor store.
     */
    public function suspend(Vendor $vendor): RedirectResponse
    {
        $vendor->status = 'suspended';
        $vendor->save();

        return back()->with('warning', "Gian hàng '{$vendor->store_name}' đã được chuyển sang trạng thái tạm khóa.");
    }

    /**
     * UC-40: Reinstate/re-activate a suspended or rejected vendor store.
     */
    public function reinstate(Vendor $vendor): RedirectResponse
    {
        $vendor->status = 'approved';
        $vendor->save();

        if ($vendor->user) {
            $vendor->user->role = 'vendor';
            $vendor->user->save();
        }

        return back()->with('success', "Gian hàng '{$vendor->store_name}' đã được mở khóa và kích hoạt lại thành công.");
    }
}
