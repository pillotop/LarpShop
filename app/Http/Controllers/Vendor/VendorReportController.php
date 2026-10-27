<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Queries\Vendors\VendorAnalyticsQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorReportController extends Controller
{
    /**
     * Display the vendor sales & inventory overview analytics report (UC-36).
     */
    public function index(Request $request, VendorAnalyticsQuery $analyticsQuery): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor');

        $validated = $request->validate([
            'range' => ['nullable', 'string', 'in:'.implode(',', array_keys(VendorAnalyticsQuery::RANGES))],
        ]);

        $range = $validated['range'] ?? '30_days';

        $data = $analyticsQuery->getOverview($vendor, $range);

        return view('vendor.reports.index', array_merge($data, [
            'vendor' => $vendor,
        ]));
    }
}
