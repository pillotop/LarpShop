@props(['order', 'canCreatePayment' => false])

<form
    action="{{ route('payments.store', $order) }}"
    method="POST"
>
    @csrf
    <input
        type="hidden"
        name="payment_option"
        value="fake_card"
    >
    <div
        class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3"
    >
        <h2
            class="h4 fw-bold mb-0"
        >
            {{ __('checkout.payment.simulated') }}
        </h2>
        <ul
            class="d-flex gap-2 list-unstyled mb-0"
            aria-label="{{ __('checkout.demo.provider') }}"
        >
            <li>
                <span class="badge theme-secondary theme-contrast border">{{ __('checkout.demo.payment') }}</span>
            </li>
            <li>
                <span class="badge theme-secondary theme-contrast border">{{ __('checkout.demo.gateway') }}</span>
            </li>
        </ul>
    </div>
    <p
        class="small fg-3"
    >
        {{ __('checkout.demo.notice') }}
    </p>

    <div
        class="bg-1 rounded-5 p-3 small mb-3"
    >
        <h3 class="h6">{{ __('checkout.demo.terms_title') }}</h3>
        <p
            class="mb-0"
        >
            {{ __('checkout.demo.terms') }}
        </p>
    </div>

    @if ($canCreatePayment)
        <div
            class="d-flex align-items-center gap-2 mb-3"
        >
            <input
                id="checkout-terms"
                name="terms"
                value="1"
                type="checkbox"
                class="check"
                required
                @checked(old('terms'))
            >
            <label
                for="checkout-terms"
                class="form-label small"
            >
                {{ __('checkout.demo.agree') }}
            </label>
        </div>
        <button
            type="submit"
            class="btn btn-solid theme-primary w-100 py-3 fw-semibold"
        >
            {{ __('checkout.demo.create', ['amount' => '$' . number_format((float) $order->total_amount, 2)]) }}
        </button>
    @else
        <p
            class="small fg-3"
            role="status"
        >
            {{ $order->status === \App\Enums\OrderStatus::Paid ? __('checkout.payment.paid') : __('checkout.demo.unavailable') }}
        </p>
        <button
            type="button"
            class="btn btn-solid theme-secondary w-100 py-3"
            disabled
        >
            {{ __('checkout.demo.disabled') }}
        </button>
    @endif
</form>
