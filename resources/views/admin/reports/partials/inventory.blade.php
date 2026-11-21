@php
    $chart = [
        'type' => 'bar',
        'integer' => true,
        'labels' => array_map(fn ($band) => __('management.report_operations.'.$band), array_keys($inventoryReport['bands'])),
        'datasets' => [['label' => __('management.products'), 'data' => array_values($inventoryReport['bands'])]],
    ];
@endphp

<section class="card p-4 h-100" data-report-chart="{{ json_encode($chart) }}">
    <h3 class="h6 fw-bold">{{ __('management.report_operations.inventory') }}</h3>
    <p class="small fg-2">{{ __('management.report_operations.inventory_note', ['threshold' => $inventoryReport['threshold']]) }}</p>
    <p class="small fw-semibold">
        {{ __('management.report_operations.inventory_summary', [
            'products' => number_format($inventoryReport['total_products']),
            'units' => number_format($inventoryReport['available_units']),
        ]) }}
    </p>
    @if ($inventoryReport['total_products'] > 0)
        <div class="admin-report-timeseries position-relative mb-3" data-chart-visual hidden>
            <canvas role="img" aria-label="{{ __('management.report_operations.inventory') }}"></canvas>
        </div>
    @endif
    <dl class="row small mb-3">
        @foreach ($inventoryReport['bands'] as $band => $count)
            <dt class="col-8 fw-normal">{{ __('management.report_operations.'.$band) }}</dt>
            <dd class="col-4 text-end">{{ number_format($count) }}</dd>
        @endforeach
    </dl>
    <a
        href="{{ route('admin.products.moderation.index', ['status' => 'pending']) }}"
        class="small fg-body mb-3"
    >
        {{ __('management.report_operations.pending_products', ['count' => number_format($inventoryReport['pending_products'])]) }}
        <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i>
    </a>
    <h4 class="h6 small fw-semibold">{{ __('management.report_operations.stock_attention') }}</h4>
    @if ($inventoryReport['attention_products']->isEmpty())
        <p class="small fg-2 mb-0">{{ __('management.report_operations.no_stock_attention') }}</p>
    @else
        <div class="table-responsive">
            <table class="table small mb-0" aria-label="{{ __('management.report_operations.stock_attention') }}">
                <thead>
                    <tr>
                        <th scope="col">{{ __('management.product') }}</th>
                        <th scope="col" class="text-end">{{ __('management.report_operations.available_units') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventoryReport['attention_products'] as $product)
                        <tr>
                            <th scope="row" class="fw-normal">
                                <a
                                    href="{{ route('products.show', ['product' => $product->product_id, 'slug' => $product->slug]) }}"
                                    class="fg-body"
                                >{{ $product->name }}</a>
                                <a
                                    href="{{ route('admin.products.moderation.index', ['status' => 'approved', 'vendor_id' => $product->vendor_id]) }}"
                                    class="small fg-2 d-block"
                                >{{ $product->vendor->store_name }}</a>
                            </th>
                            <td class="text-end">{{ number_format($product->stock) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
