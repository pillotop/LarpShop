<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Queries\Vendors\VendorAnalyticsQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorReportController extends Controller
{
    public function __construct(
        private VendorAnalyticsQuery $analytics,
    ) {}

    /**
     * Display the vendor sales & inventory overview analytics report (UC-36).
     */
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $validated = $request->validate([
            'range' => ['nullable', 'string', Rule::in(array_keys(VendorAnalyticsQuery::RANGES))],
        ]);

        $range = $validated['range'] ?? '30_days';

        $data = $this->analytics->getOverview($vendor, $range);

        return view('vendor.reports.index', array_merge($data, [
            'vendor' => $vendor,
        ]));
    }
}
