@extends('layouts.admin')

@section('title', __('management.platform_reports'))
@section('page_title', __('management.platform_reports'))

@section('content')
<div class="container-fluid p-0">
    @include('admin.reports.partials.filters')
    @include('admin.reports.partials.metrics')
    @include('admin.reports.partials.trends')
    @include('admin.reports.partials.payments')

    <p class="small fg-2 mb-3">{{ __('management.report_copy.orders_note') }}</p>
    <x-admin.order-status-chart
        :distribution="$statusDistribution"
        :total="$metrics['total_orders']"
    />

    <div class="row g-4">
        <div class="col-12 xl:col-6">
            <div class="card border-0 shadow-sm p-4 bg-body h-100">
                <h2 class="h6 fw-bold mb-3">{{ __('management.top_5_products_by_revenue') }}</h2>
                @if ($topProducts->isEmpty())
                    <div class="fg-3 small">{{ __('management.no_sales_in_this_period') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-1 small fg-3 text-uppercase">
                                <tr><th>{{ __('management.product') }}</th><th class="text-end">{{ __('management.units') }}</th><th class="text-end">{{ __('management.revenue') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($topProducts as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $row->name }}</div>
                                            <small class="fg-3">{{ $row->store_name }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($row->units_sold) }}</td>
                                        <td class="text-end">{{ number_format($row->revenue, 2) }} {{ $reportCurrency }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 xl:col-6">
            <div class="card border-0 shadow-sm p-4 bg-body h-100">
                <h2 class="h6 fw-bold mb-3">{{ __('management.top_5_vendors_by_revenue') }}</h2>
                @if ($topVendors->isEmpty())
                    <div class="fg-3 small">{{ __('management.no_sales_in_this_period') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-1 small fg-3 text-uppercase">
                                <tr><th>{{ __('management.vendor') }}</th><th class="text-end">{{ __('management.units') }}</th><th class="text-end">{{ __('management.revenue') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($topVendors as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->store_name }}</td>
                                        <td class="text-end">{{ number_format($row->units_sold) }}</td>
                                        <td class="text-end">{{ number_format($row->revenue, 2) }} {{ $reportCurrency }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 bg-body">
                <h2 class="h6 fw-bold mb-3">{{ __('management.approved_products_per_category_top_10') }}</h2>
                <p class="small fg-2">{{ __('management.report_copy.snapshot_note') }}</p>
                @php $maxCount = max(1, (int) $categoryDistribution->max('products_count')); @endphp
                @forelse ($categoryDistribution as $category)
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div style="width: 180px;" class="text-truncate">{{ $category->name }}</div>
                        <div class="progress flex-grow-1" style="height: 14px;">
                            <div class="progress-bar" style="width: {{ ($category->products_count / $maxCount) * 100 }}%"></div>
                        </div>
                        <div class="small fw-bold" style="width: 40px;">{{ $category->products_count }}</div>
                    </div>
                @empty
                    <div class="fg-3 small">{{ __('management.no_categories_yet') }}</div>
                @endforelse
            </div>
        </div>
    </div>
    @include('admin.reports.partials.operations')
</div>
@endsection

@push('scripts')
    @vite('resources/js/admin/order-status-chart.js')
    @vite('resources/js/admin/report-charts.js')
@endpush
