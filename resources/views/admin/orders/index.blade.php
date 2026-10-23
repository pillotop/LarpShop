@extends('layouts.admin')

@section('title', 'Order Investigation (UC-44)')
@section('page_title', 'Orders & Disputes')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="pb-3 border-bottom mb-4">
            <h1 class="h5 fw-bold text-dark mb-1"><i class="bi bi-receipt text-primary me-2"></i>Order Investigation</h1>
            <p class="text-muted small mb-0">Search every order on the marketplace and open one to review its items, vendors, and totals.</p>
        </div>

        <ul class="nav nav-pills gap-2 mb-3">
            @foreach (array_merge(['all'], $statuses) as $key)
                <li class="nav-item">
                    <a href="{{ route('admin.orders.index', array_filter(['status' => $key, 'q' => $keyword])) }}"
                        class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $status === $key ? 'active' : 'bg-light text-dark' }}">
                        {{ ucfirst($key) }} <span class="badge bg-secondary rounded-pill ms-1">{{ $counts[$key] ?? 0 }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 mb-4">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="col-12 col-md-9">
                <input type="text" name="q" value="{{ $keyword }}" maxlength="100" class="form-control" placeholder="Search by order # or customer username / email">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1">Search</button>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        @if ($orders->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No orders match your filters.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th>Placed</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="fw-bold">#{{ $order->order_id }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $order->user?->username ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $order->user?->email }}</small>
                                </td>
                                <td class="text-center">{{ $order->details_count }}</td>
                                <td class="text-end">{{ number_format((float) $order->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge rounded-pill {{ $order->status === 'disputed' ? 'bg-danger' : ($order->status === 'delivered' ? 'bg-success' : 'bg-secondary') }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td class="small text-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order->order_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Investigate</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing <strong>{{ $orders->firstItem() }}</strong> to <strong>{{ $orders->lastItem() }}</strong> of <strong>{{ $orders->total() }}</strong> orders
                </div>
                <div>{{ $orders->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
