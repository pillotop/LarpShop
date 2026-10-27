@props(['user', 'order'])
<section aria-labelledby="checkout-details-title">
    <h1 id="checkout-details-title" class="h4 fw-bold mb-3">Your details</h1>
    <p class="text-muted">Review your contact information before paying.</p>
    <dl class="row g-2 mb-3">
        <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $user->username }}</dd>
        <dt class="col-sm-4">Email</dt><dd class="col-sm-8 text-break">{{ $user->email }}</dd>
        <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $user->phone_number ?: 'Not provided' }}</dd>
        <dt class="col-sm-4">Order</dt><dd class="col-sm-8">#{{ $order->getKey() }} · {{ ucfirst($order->status->value) }}</dd>
        @if ($order->status === \App\Enums\OrderStatus::Pending && $order->expires_at)
            <dt class="col-sm-4">Reservation ends</dt>
            <dd class="col-sm-8"><time datetime="{{ $order->expires_at->toIso8601String() }}">{{ $order->expires_at->format('d M Y, H:i T') }}</time></dd>
        @endif
    </dl>
    <a href="{{ route('profile') }}#pane-customer" class="small">Edit contact details</a>
</section>
