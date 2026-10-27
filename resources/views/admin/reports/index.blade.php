@extends('layouts.admin')

@section('title', 'Platform Reports (UC-45)')
@section('page_title', 'Platform Reports')

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
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h5 fw-bold text-dark mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Platform Overview</h1>
                <p class="text-muted small mb-0">Revenue counts orders with a verified successful payment. Delivery tracking is not available yet.</p>
            </div>
            <form method="GET" action="{{ route('admin.reports.index') }}" class="d-flex gap-2">
                <select name="range" class="form-select" onchange="this.form.submit()">
                    @foreach ($ranges as $value => $label)
                        <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-dark">Apply</button></noscript>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-muted small">Gross revenue</div>
                <div class="fs-4 fw-bold text-success">${{ number_format($metrics['gross_revenue'], 2) }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-muted small">Total orders</div>
                <div class="fs-4 fw-bold">{{ number_format($metrics['total_orders']) }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-muted small">Average order value</div>
                <div class="fs-4 fw-bold">${{ number_format($metrics['average_order_value'], 2) }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-muted small">Paid / Cancelled+Expired</div>
                <div class="fs-4 fw-bold">
                    <span class="text-success">{{ $metrics['paid_rate'] }}%</span>
                    <span class="text-muted">/</span>
                    <span class="text-danger">{{ $metrics['problem_rate'] }}%</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-muted small">Active vendors</div>
                <div class="fs-4 fw-bold">{{ number_format($metrics['active_vendors']) }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-muted small">Approved products</div>
                <div class="fs-4 fw-bold">{{ number_format($metrics['approved_products']) }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h2 class="h6 fw-bold mb-3">Order status distribution</h2>
        @if ($metrics['total_orders'] === 0)
            <div class="text-muted small">No orders in this period.</div>
        @else
            <div class="progress mb-3" style="height: 22px;">
                @foreach ($statusDistribution as $status => $data)
                    @if ($data['count'] > 0)
                        <div class="progress-bar bg-{{ $statusColors[$status] ?? 'secondary' }}" style="width: {{ $data['percent'] }}%" title="{{ ucfirst($status) }}: {{ $data['count'] }}"></div>
                    @endif
                @endforeach
            </div>
            <div class="d-flex flex-wrap gap-3 small">
                @foreach ($statusDistribution as $status => $data)
                    <span><span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }}">&nbsp;</span> {{ ucfirst($status) }}: <strong>{{ $data['count'] }}</strong> ({{ $data['percent'] }}%)</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h2 class="h6 fw-bold mb-3">Top 5 products by revenue</h2>
                @if ($topProducts->isEmpty())
                    <div class="text-muted small">No sales in this period.</div>
                @else
                    <table class="table align-middle mb-0">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr><th>Product</th><th class="text-end">Units</th><th class="text-end">Revenue</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $row->name }}</div>
                                        <small class="text-muted">{{ $row->store_name }}</small>
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

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h2 class="h6 fw-bold mb-3">Top 5 vendors by revenue</h2>
                @if ($topVendors->isEmpty())
                    <div class="text-muted small">No sales in this period.</div>
                @else
                    <table class="table align-middle mb-0">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr><th>Vendor</th><th class="text-end">Units</th><th class="text-end">Revenue</th></tr>
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
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h2 class="h6 fw-bold mb-3">Approved products per category (top 10)</h2>
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
                    <div class="text-muted small">No categories yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
