@php
    $columns = ['attempts', 'succeeded', 'failed', 'in_progress', 'cancelled', 'refund_related', 'other'];
    $datasets = [];
    foreach (array_slice($columns, 1) as $column) {
        $datasets[] = [
            'label' => __('management.report_copy.payment_columns.'.$column),
            'data' => array_column($paymentReport['providers'], $column),
        ];
    }
    $chart = [
        'type' => 'bar',
        'stacked' => true,
        'integer' => true,
        'labels' => array_map(
            fn ($row) => $row['label'].($row['simulated'] ? ' ('.__('management.report_copy.simulation').')' : ''),
            $paymentReport['providers'],
        ),
        'datasets' => $datasets,
    ];
@endphp

<section class="card p-4 mb-4" data-report-chart="{{ json_encode($chart) }}">
    <h2 class="h6 fw-bold">{{ __('management.report_copy.payment_activity') }}</h2>
    <p class="small fg-2">{{ __('management.report_copy.attempt_note') }}</p>
    @if ($paymentReport['total_attempts'] === 0)
        <p class="small fg-2 mb-0">{{ __('management.report_copy.no_attempts') }}</p>
    @else
        <p class="small fw-semibold">
            {{ __('management.report_copy.attempt_summary', [
                'attempts' => number_format($paymentReport['total_attempts']),
                'orders' => number_format($paymentReport['unique_orders']),
                'rate' => $paymentReport['success_rate'],
            ]) }}
        </p>
        <div class="admin-report-timeseries position-relative mb-3" data-chart-visual hidden>
            <canvas
                role="img"
                aria-label="{{ __('management.report_copy.payment_activity') }}"
            ></canvas>
        </div>
        <div class="table-responsive">
            <table class="table align-middle small mb-0">
                <caption class="visually-hidden">{{ __('management.report_copy.payment_activity') }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('management.report_copy.provider') }}</th>
                        @foreach ($columns as $column)
                            <th scope="col" class="text-end">{{ __('management.report_copy.payment_columns.'.$column) }}</th>
                        @endforeach
                        <th scope="col" class="text-end">{{ __('management.report_copy.success_rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($paymentReport['providers'] as $row)
                        <tr>
                            <th scope="row">
                                {{ $row['label'] }}
                                @if ($row['simulated'])
                                    <small class="d-block fg-2 fw-normal">{{ __('management.report_copy.simulation') }}</small>
                                @endif
                            </th>
                            @foreach ($columns as $column)
                                <td class="text-end">{{ number_format($row[$column]) }}</td>
                            @endforeach
                            <td class="text-end">{{ $row['success_rate'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
