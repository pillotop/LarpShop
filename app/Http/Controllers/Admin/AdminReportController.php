<?php

namespace App\Http\Controllers\Admin;

use App\Data\Reports\ReportPeriod;
use App\Http\Controllers\Controller;
use App\Queries\Reports\PlatformReportQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request, PlatformReportQuery $reports): View
    {
        $validated = $request->validate([
            'range' => ['nullable', 'string', Rule::in(ReportPeriod::RANGES)],
            'sales_source' => ['nullable', 'string', Rule::in(PlatformReportQuery::SALES_SOURCES)],
        ]);

        $range = $validated['range'] ?? '30_days';
        $salesSource = $validated['sales_source'] ?? 'all';
        $period = ReportPeriod::forRange($range);

        return view('admin.reports.index', [
            ...$reports->overview($period, $salesSource),
            'range' => $range,
            'ranges' => ReportPeriod::RANGES,
            'salesSource' => $salesSource,
            'salesSources' => PlatformReportQuery::SALES_SOURCES,
            'reportPeriod' => $period,
            'reportCurrency' => config('currency.base', 'USD'),
        ]);
    }
}
