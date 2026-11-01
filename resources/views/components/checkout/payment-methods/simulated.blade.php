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
            Simulated payment
        </h2>
        <ul
            class="d-flex gap-2 list-unstyled mb-0"
            aria-label="Demo payment provider"
        >
            <li>
                <span class="badge theme-secondary theme-contrast border">Demo payment</span>
            </li>
            <li>
                <span class="badge theme-secondary theme-contrast border">Fake gateway</span>
            </li>
        </ul>
    </div>
    <p
        class="small fg-3"
    >
        No real card details are collected and no money is charged.
    </p>

    <div
        class="bg-1 rounded-5 p-3 small mb-3"
    >
        <h3 class="h6">Terms &amp; conditions</h3>
        <p
            class="mb-0"
        >
            Create a demo session, then choose a simulated outcome. Review the order total and shipping address before continuing.
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
                I understand and agree to the demo checkout terms above.
            </label>
        </div>
        <button
            type="submit"
            class="btn btn-solid theme-primary w-100 py-3 fw-semibold"
        >
            Create demo payment — ${{ number_format((float) $order->total_amount, 2) }}
        </button>
    @else
        <p
            class="small fg-3"
            role="status"
        >
            {{ $order->status === \App\Enums\OrderStatus::Paid ? 'This order has been paid.' : 'Payment is unavailable. The order may be closed, have a payment in progress, or need a shipping address.' }}
        </p>
        <button
            type="button"
            class="btn btn-solid theme-secondary w-100 py-3"
            disabled
        >
            Payment unavailable
        </button>
    @endif
</form>
