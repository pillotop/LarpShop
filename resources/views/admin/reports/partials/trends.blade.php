@php
    $points = $trends['points'];
    $labels = array_column($points, 'date');
    $charts = [
        'sales_trend' => [
            'labels' => $labels,
            'currency' => $reportCurrency,
            'datasets' => [
                ['label' => __('management.report_copy.gross_sales'), 'data' => array_column($points, 'revenue')],
            ],
        ],
        'order_trend' => [
            'labels' => $labels,
            'integer' => true,
            'datasets' => [
                ['label' => __('management.report_copy.paid_orders'), 'data' => array_column($points, 'paid_orders')],
                ['label' => __('management.report_copy.created_orders'), 'data' => array_column($points, 'created_orders')],
            ],
        ],
    ];
@endphp

<section class="mb-4">
    <h2 class="h6 fw-bold">{{ __('management.report_copy.trends') }}</h2>
    <p class="small fg-2">{{ __('management.report_copy.trend_notes.'.$trends['granularity']) }}</p>
    <div class="row g-3">
        @foreach ($charts as $title => $chart)
            <div class="col-12 xl:col-6">
                <div class="card p-4 h-100" data-report-chart="{{ json_encode($chart) }}">
                    <h3 class="h6 fw-semibold">{{ __('management.report_copy.'.$title) }}</h3>
                    <div class="admin-report-timeseries position-relative" data-chart-visual hidden>
                        <canvas
                            role="img"
                            aria-label="{{ __('management.report_copy.'.$title) }}"
                        ></canvas>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <details class="border p-3 mt-3">
        <summary>{{ __('management.report_copy.view_trend_data') }}</summary>
        <div class="table-responsive mt-3">
            <table class="table small mb-0">
                <caption class="visually-hidden">{{ __('management.report_copy.trends') }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('management.report_copy.bucket.'.$trends['granularity']) }}</th>
                        <th scope="col" class="text-end">{{ __('management.report_copy.gross_sales') }} ({{ $reportCurrency }})</th>
                        <th scope="col" class="text-end">{{ __('management.report_copy.paid_orders') }}</th>
                        <th scope="col" class="text-end">{{ __('management.report_copy.created_orders') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($points as $point)
                        <tr>
                            <th scope="row">{{ $point['date'] }}</th>
                            <td class="text-end">{{ number_format($point['revenue'], 2) }}</td>
                            <td class="text-end">{{ number_format($point['paid_orders']) }}</td>
                            <td class="text-end">{{ number_format($point['created_orders']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>
