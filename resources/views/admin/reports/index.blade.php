@extends('layouts.admin')

@section('title', __('management.platform_reports').' (UC-45)')
@section('page_title', __('management.platform_reports'))

@php
    $statusColors = [
        'pending' => 'secondary',
        'paid' => 'success',
        'cancelled' => 'dark',
        'expired' => 'warning',
    ];
@endphp

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h5 fw-bold fg-body mb-1"><i class="bi bi-graph-up-arrow fg-primary me-2"></i>{{ __('management.platform_overview') }}</h1>
                <p class="fg-3 small mb-0">{{ __('management.report_copy.revenue_note') }}</p>
            </div>
            <form method="GET" action="{{ route('admin.reports.index') }}" class="d-flex gap-2">
                <select name="range" class="form-control" onchange="this.form.submit()">
                    @foreach ($ranges as $value => $label)
                        <option value="{{ $value }}" @selected($range === $value)>{{ __('management.periods.'.$value) }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-solid theme-inverse">{{ __('management.apply') }}</button></noscript>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 sm:col-6 xl:col-4">
            <div class="card border-0 shadow-sm rounded-9 p-3 h-100">
                <div class="fg-3 small">{{ __('management.gross_revenue') }}</div>
                <div class="fs-lg fw-bold fg-success">${{ number_format($metrics['gross_revenue'], 2) }}</div>
            </div>
        </div>
        <div class="col-12 sm:col-6 xl:col-4">
            <div class="card border-0 shadow-sm rounded-9 p-3 h-100">
                <div class="fg-3 small">{{ __('management.total_orders') }}</div>
                <div class="fs-lg fw-bold">{{ number_format($metrics['total_orders']) }}</div>
            </div>
        </div>
        <div class="col-12 sm:col-6 xl:col-4">
            <div class="card border-0 shadow-sm rounded-9 p-3 h-100">
                <div class="fg-3 small">{{ __('management.average_order_value') }}</div>
                <div class="fs-lg fw-bold">${{ number_format($metrics['average_order_value'], 2) }}</div>
            </div>
        </div>
        <div class="col-12 sm:col-6 xl:col-4">
            <div class="card border-0 shadow-sm rounded-9 p-3 h-100">
                <div class="fg-3 small">{{ __('management.paid_cancelled_expired') }}</div>
                <div class="fs-lg fw-bold">
                    <span class="fg-success">{{ $metrics['paid_rate'] }}%</span>
                    <span class="fg-3">/</span>
                    <span class="fg-danger">{{ $metrics['problem_rate'] }}%</span>
                </div>
            </div>
        </div>
        <div class="col-12 sm:col-6 xl:col-4">
            <div class="card border-0 shadow-sm rounded-9 p-3 h-100">
                <div class="fg-3 small">{{ __('management.active_vendors') }}</div>
                <div class="fs-lg fw-bold">{{ number_format($metrics['active_vendors']) }}</div>
            </div>
        </div>
        <div class="col-12 sm:col-6 xl:col-4">
            <div class="card border-0 shadow-sm rounded-9 p-3 h-100">
                <div class="fg-3 small">{{ __('management.approved_products') }}</div>
                <div class="fs-lg fw-bold">{{ number_format($metrics['approved_products']) }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
        <h2 class="h6 fw-bold mb-3">{{ __('management.order_status_distribution') }}</h2>
        @if ($metrics['total_orders'] === 0)
            <div class="fg-3 small">{{ __('management.no_orders_in_this_period') }}</div>
        @else
            <div class="progress mb-3" style="height: 22px;">
                @foreach ($statusDistribution as $status => $data)
                    @if ($data['count'] > 0)
                        <div class="progress-bar bg-{{ $statusColors[$status] ?? 'secondary' }}" style="width: {{ $data['percent'] }}%" title="{{ __('checkout.order_status.'.$status) }}: {{ $data['count'] }}"></div>
                    @endif
                @endforeach
            </div>
            <div class="d-flex flex-wrap gap-3 small">
                @foreach ($statusDistribution as $status => $data)
                    <span><span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }}">&nbsp;</span> {{ __('checkout.order_status.'.$status) }}: <strong>{{ $data['count'] }}</strong> ({{ $data['percent'] }}%)</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-12 xl:col-6">
            <div class="card border-0 shadow-sm rounded-9 p-4 bg-white h-100">
                <h2 class="h6 fw-bold mb-3">{{ __('management.top_5_products_by_revenue') }}</h2>
                @if ($topProducts->isEmpty())
                    <div class="fg-3 small">{{ __('management.no_sales_in_this_period') }}</div>
                @else
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
                                    <td class="text-end">${{ number_format($row->revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="col-12 xl:col-6">
            <div class="card border-0 shadow-sm rounded-9 p-4 bg-white h-100">
                <h2 class="h6 fw-bold mb-3">{{ __('management.top_5_vendors_by_revenue') }}</h2>
                @if ($topVendors->isEmpty())
                    <div class="fg-3 small">{{ __('management.no_sales_in_this_period') }}</div>
                @else
                    <table class="table align-middle mb-0">
                        <thead class="bg-1 small fg-3 text-uppercase">
                            <tr><th>{{ __('management.vendor') }}</th><th class="text-end">{{ __('management.units') }}</th><th class="text-end">{{ __('management.revenue') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($topVendors as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->store_name }}</td>
                                    <td class="text-end">{{ number_format($row->units_sold) }}</td>
                                    <td class="text-end">${{ number_format($row->revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-9 p-4 bg-white">
                <h2 class="h6 fw-bold mb-3">{{ __('management.approved_products_per_category_top_10') }}</h2>
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
</div>
@endsection
