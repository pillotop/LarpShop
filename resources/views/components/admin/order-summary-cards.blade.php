@props([
    'order',
    'computedTotal',
    'totalMatches',
    'statuses' => [],
])

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
