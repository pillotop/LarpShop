<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * UC-38: Display the administrator dashboard with vital platform metrics.
     */
    public function index(): View
    {
        $metrics = [
            'total_users' => User::count(),
            'total_customers' => User::where('role', 'customer')->count(),
            'total_vendors' => Vendor::whereIn('status', ['active', 'approved'])->count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'total_products' => Product::count(),
        ];

        $pendingVendorRequests = Vendor::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(6)
            ->get();

        $recentVendors = Vendor::with('user')
            ->whereIn('status', ['active', 'approved'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('metrics', 'pendingVendorRequests', 'recentVendors'));
    }
}
