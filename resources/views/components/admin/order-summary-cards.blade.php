@props([
    'order',
    'computedTotal',
    'totalMatches',
    'canCancel' => false,
])

<div class="row g-4 mb-4">
    <div class="col-12 lg:col-4">
        <div class="card border-0 shadow-sm rounded-9 p-4 h-100">
            <h2 class="h6 fw-bold mb-3">{{ __('management.customer') }}</h2>
            <div class="fw-semibold">{{ $order->user?->username ?? __('management.n_a') }}</div>
            <div class="fg-3 small">{{ $order->user?->email }}</div>
            <div class="fg-3 small">{{ $order->user?->phone_number ?: __('management.no_phone_number') }}</div>
            <div class="fg-3 small mt-2">{{ __('management.display.placed') }} {{ $order->created_at?->format('d/m/Y H:i') }}</div>
        </div>
    </div>
    <div class="col-12 lg:col-4">
        <div class="card border-0 shadow-sm rounded-9 p-4 h-100">
            <h2 class="h6 fw-bold mb-3">{{ __('management.totals') }}</h2>
            <div class="d-flex justify-content-between"><span>{{ __('management.recorded_total') }}</span><strong>{{ number_format((float) $order->total_amount, 2) }}</strong></div>
            <div class="d-flex justify-content-between"><span>{{ __('management.sum_of_items') }}</span><strong>{{ number_format((float) $computedTotal, 2) }}</strong></div>
            @if ($totalMatches)
                <div class="alert theme-success py-2 mt-3 mb-0 small"><i class="bi bi-check-circle me-1"></i>{{ __('management.totals_match') }}</div>
            @else
                <div class="alert theme-danger py-2 mt-3 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i>{{ __('management.total_mismatch_detected_investigate_this_order') }}</div>
            @endif
        </div>
    </div>
    <div class="col-12 lg:col-4">
        <div class="card border-0 shadow-sm rounded-9 p-4 h-100">
            <h2 class="h6 fw-bold mb-3">{{ __('management.status') }}</h2>
            <p>{{ __('management.display.order') }} {{ __('checkout.order_status.'.$order->status->value) }}</p>
            @forelse ($order->payments as $payment)
                <p class="small mb-2">
                    Payment #{{ $payment->getKey() }} ({{ $payment->provider }}): {{ __('checkout.payment_status.'.$payment->status->value) }}
                </p>
            @empty
                <p class="small fg-3">{{ __('management.no_payment_attempts_recorded') }}</p>
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
                        class="btn btn-outline theme-danger"
                    >
                        {{ __('management.cancel_order') }}
                    </button>
                </form>
            @else
                <p class="small fg-3">{{ __('management.this_order_cannot_be_cancelled') }}</p>
            @endif
            <p class="small fg-3 mt-3 mb-0">{{ __('management.delivery_tracking_is_not_available_yet') }}</p>
        </div>
    </div>
</div>
