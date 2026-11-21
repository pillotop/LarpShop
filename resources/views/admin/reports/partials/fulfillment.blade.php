@php
    $labels = array_map(
        fn ($status) => $status === 'other'
            ? __('management.report_copy.payment_columns.other')
            : __('management.fulfillment_labels.'.$status),
        array_keys($fulfillmentReport['statuses']),
    );
    $chart = [
        'type' => 'bar',
        'integer' => true,
        'labels' => $labels,
        'datasets' => [['label' => __('management.report_operations.order_lines'), 'data' => array_column($fulfillmentReport['statuses'], 'lines')]],
    ];
@endphp

<section class="card p-4 h-100" data-report-chart="{{ json_encode($chart) }}">
    <h3 class="h6 fw-bold">{{ __('management.report_operations.fulfillment') }}</h3>
    <p class="small fg-2">{{ __('management.report_operations.fulfillment_note') }}</p>
    <p class="small fw-semibold">
        {{ __('management.report_operations.fulfillment_summary', [
            'orders' => number_format($fulfillmentReport['orders']),
            'lines' => number_format($fulfillmentReport['lines']),
            'units' => number_format($fulfillmentReport['units']),
        ]) }}
    </p>
    @if ($fulfillmentReport['lines'] > 0)
        <div class="admin-report-timeseries position-relative mb-3" data-chart-visual hidden>
            <canvas role="img" aria-label="{{ __('management.report_operations.fulfillment') }}"></canvas>
        </div>
    @endif
    <div class="table-responsive mb-3">
        <table class="table small mb-0" aria-label="{{ __('management.report_operations.fulfillment') }}">
            <thead>
                <tr>
                    <th scope="col">{{ __('management.status') }}</th>
                    <th scope="col" class="text-end">{{ __('management.report_operations.order_lines') }}</th>
                    <th scope="col" class="text-end">{{ __('management.units') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach (array_values($fulfillmentReport['statuses']) as $index => $counts)
                    <tr>
                        <th scope="row" class="fw-normal">{{ $labels[$index] }}</th>
                        <td class="text-end">{{ number_format($counts['lines']) }}</td>
                        <td class="text-end">{{ number_format($counts['units']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <h4 class="h6 small fw-semibold">
        {{ __('management.report_operations.dispatch_queue', ['count' => number_format($fulfillmentReport['awaiting_dispatch'])]) }}
    </h4>
    <p class="small fg-2">{{ __('management.report_operations.dispatch_note') }}</p>
    <ul class="list-unstyled small mb-0">
        @forelse ($fulfillmentReport['oldest_lines'] as $line)
            <li class="border-top py-2">
                <a href="{{ route('admin.orders.show', $line->order_id) }}" class="fg-body">
                    #{{ $line->order_id }} · {{ $line->name }} × {{ $line->quantity }}
                </a>
                <span class="d-block fg-2">
                    {{ $line->store_name }} · {{ __('management.report_operations.order_created', ['time' => $line->ordered_at]) }}
                </span>
            </li>
        @empty
            <li class="fg-2">{{ __('management.report_operations.no_dispatch_queue') }}</li>
        @endforelse
    </ul>
</section>
