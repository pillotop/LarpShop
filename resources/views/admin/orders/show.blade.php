@extends('layouts.admin')

@section('title', 'Order #'.$order->order_id.' (UC-44)')
@section('page_title', 'Order #'.$order->order_id)

@section('content')
<div class="container-fluid p-0">
    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-light rounded-pill mb-3"><i class="bi bi-arrow-left"></i> Back to orders</a>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h2 class="h6 fw-bold mb-3">Customer</h2>
                <div class="fw-semibold">{{ $order->user?->username ?? 'N/A' }}</div>
                <div class="text-muted small">{{ $order->user?->email }}</div>
                <div class="text-muted small">{{ $order->user?->phone_number ?: 'No phone number' }}</div>
                <div class="text-muted small mt-2">Placed {{ $order->created_at?->format('d/m/Y H:i') }}</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h2 class="h6 fw-bold mb-3">Totals</h2>
                <div class="d-flex justify-content-between"><span>Recorded total</span><strong>{{ number_format((float) $order->total_amount, 2) }}</strong></div>
                <div class="d-flex justify-content-between"><span>Sum of items</span><strong>{{ number_format((float) $computedTotal, 2) }}</strong></div>
                @if ($totalMatches)
                    <div class="alert alert-success py-2 mt-3 mb-0 small"><i class="bi bi-check-circle me-1"></i>Totals match.</div>
                @else
                    <div class="alert alert-danger py-2 mt-3 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i>Total mismatch detected. Investigate this order.</div>
                @endif
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h2 class="h6 fw-bold mb-3">Status</h2>
                <form method="POST" action="{{ route('admin.orders.update-status', $order->order_id) }}" class="d-flex gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="form-select">
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}" @selected($order->status === $statusOption)>{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>

    @forelse ($byVendor as $vendorId => $details)
        @php $vendor = $details->first()->product?->vendor; @endphp
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-bold mb-0"><i class="bi bi-shop me-2"></i>{{ $vendor?->store_name ?? 'Unknown store' }}</h2>
                @if ($vendor?->user)
                    <small class="text-muted">Owner: {{ $vendor->user->username }}</small>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit price</th><th class="text-end">Subtotal</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $detail)
                            <tr>
                                <td>{{ $detail->product?->name ?? 'Deleted product #'.$detail->product_id }}</td>
                                <td class="text-center">{{ $detail->quantity }}</td>
                                <td class="text-end">{{ number_format((float) $detail->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format($detail->quantity * (float) $detail->unit_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center text-muted">This order has no line items.</div>
    @endforelse
</div>
@endsection
