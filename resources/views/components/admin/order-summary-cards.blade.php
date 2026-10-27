@props([
    'order',
    'computedTotal',
    'totalMatches',
    'canCancel' => false,
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
            <p>Order: {{ ucfirst($order->status->value) }}</p>
            @forelse ($order->payments as $payment)
                <p class="small mb-2">
                    Payment #{{ $payment->getKey() }} ({{ $payment->provider }}): {{ ucfirst(str_replace('_', ' ', $payment->status->value)) }}
                </p>
            @empty
                <p class="small text-muted">No payment attempts recorded.</p>
            @endforelse

            @if ($canCancel)
                <form
                    method="POST"
                    action="{{ route('admin.orders.update-status', $order->order_id) }}"
                >
                    @csrf
                    @method('PATCH')
                    <input
                        type="hidden"
                        name="status"
                        value="cancelled"
                    >
                    <button
                        type="submit"
                        class="btn btn-outline-danger"
                    >
                        Cancel order
                    </button>
                </form>
            @else
                <p class="small text-muted">This order cannot be cancelled.</p>
            @endif
            <p class="small text-muted mt-3 mb-0">Delivery tracking is not available yet.</p>
        </div>
    </div>
</div>
