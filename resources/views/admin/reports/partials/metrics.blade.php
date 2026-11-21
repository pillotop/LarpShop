@php
    $salesMetrics = [
        'gross_sales' => number_format($metrics['gross_revenue'], 2).' '.$reportCurrency,
        'paid_orders' => number_format($metrics['paid_orders']),
        'average_paid_order' => number_format($metrics['average_order_value'], 2).' '.$reportCurrency,
    ];
    $orderMetrics = [
        'created_orders' => number_format($metrics['total_orders']),
        'order_rates' => $metrics['paid_rate'].'% / '.$metrics['problem_rate'].'%',
    ];
    $snapshotMetrics = [
        'active_vendors' => number_format($metrics['active_vendors']),
        'approved_products' => number_format($metrics['approved_products']),
    ];
    $comparisonKeys = [
        'gross_sales' => 'gross_revenue',
        'paid_orders' => 'paid_orders',
        'average_paid_order' => 'average_order_value',
        'created_orders' => 'total_orders',
    ];
@endphp

<section class="mb-4">
    <h2 class="h6 fw-bold">{{ __('management.report_copy.completed_sales') }}</h2>
    <p class="small fg-2 mb-2">{{ __('management.report_copy.revenue_note', ['currency' => $reportCurrency]) }}</p>
    <p class="small fg-2">{{ __('management.report_copy.source_note') }}</p>
    <p class="small fg-2">
        @if ($comparison)
            {{ __('management.report_copy.comparison_period', [
                'start' => $comparison['period']->since->format('Y-m-d H:i'),
                'end' => $comparison['period']->until->format('Y-m-d H:i'),
                'timezone' => $comparison['period']->until->timezoneName,
            ]) }}
        @else
            {{ __('management.report_copy.no_comparison') }}
        @endif
    </p>
    @if ($undatedSuccesses > 0)
        <p class="small border p-3">{{ __('management.report_copy.undated_successes', ['count' => $undatedSuccesses]) }}</p>
    @endif
    <div class="row g-3">
        @foreach ($salesMetrics as $label => $value)
            <div class="col-12 md:col-4">
                <div class="card p-3 h-100">
                    <div class="small fg-2">{{ __('management.report_copy.'.$label) }}</div>
                    <div class="fs-lg fw-bold mt-1">{{ $value }}</div>
                    @include('admin.reports.partials.change', [
                        'change' => $comparison['metrics'][$comparisonKeys[$label]] ?? null,
                        'isMoney' => $label !== 'paid_orders',
                    ])
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="mb-4">
    <h2 class="h6 fw-bold">{{ __('management.report_copy.created_orders') }}</h2>
    <div class="row g-3">
        @foreach ($orderMetrics as $label => $value)
            <div class="col-12 md:col-6">
                <div class="card p-3 h-100">
                    <div class="small fg-2">{{ __('management.report_copy.'.$label) }}</div>
                    <div class="fs-lg fw-bold mt-1">{{ $value }}</div>
                    @include('admin.reports.partials.change', [
                        'change' => $comparison['metrics'][$comparisonKeys[$label] ?? ''] ?? null,
                        'isMoney' => false,
                    ])
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="mb-4">
    <h2 class="h6 fw-bold">{{ __('management.report_copy.current_snapshot') }}</h2>
    <p class="small fg-2">{{ __('management.report_copy.snapshot_note') }}</p>
    <div class="row g-3">
        @foreach ($snapshotMetrics as $label => $value)
            <div class="col-12 md:col-6">
                <div class="card p-3 h-100">
                    <div class="small fg-2">{{ __('management.'.$label) }}</div>
                    <div class="fs-lg fw-bold mt-1">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>
</section>
