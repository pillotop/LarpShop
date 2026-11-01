@props(['user', 'order'])
<section aria-labelledby="checkout-details-title">
    <h1 id="checkout-details-title" class="h4 fw-bold mb-3">{{ __('checkout.details.title') }}</h1>
    <p class="fg-3">{{ __('checkout.details.intro') }}</p>
    <dl class="row g-2 mb-3">
        <dt class="sm:col-4">{{ __('checkout.details.name') }}</dt><dd class="sm:col-8">{{ $user->username }}</dd>
        <dt class="sm:col-4">{{ __('checkout.details.email') }}</dt><dd class="sm:col-8 text-break">{{ $user->email }}</dd>
        <dt class="sm:col-4">{{ __('checkout.details.phone') }}</dt><dd class="sm:col-8">{{ $user->phone_number ?: __('checkout.details.not_provided') }}</dd>
        <dt class="sm:col-4">{{ __('checkout.details.order') }}</dt><dd class="sm:col-8">#{{ $order->getKey() }} · {{ __('checkout.order_status.' . $order->status->value) }}</dd>
        @if ($order->status === \App\Enums\OrderStatus::Pending && $order->expires_at)
            <dt class="sm:col-4">{{ __('checkout.details.reservation') }}</dt>
            <dd class="sm:col-8"><time datetime="{{ $order->expires_at->toIso8601String() }}">{{ $order->expires_at->locale(app()->getLocale())->translatedFormat('d M Y, H:i T') }}</time></dd>
        @endif
    </dl>
    <a href="{{ route('profile') }}#pane-customer" class="small">{{ __('checkout.details.edit') }}</a>
</section>
