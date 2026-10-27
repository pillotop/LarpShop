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
                <span class="badge text-bg-light border">Demo payment</span>
            </li>
            <li>
                <span class="badge text-bg-light border">Fake gateway</span>
            </li>
        </ul>
    </div>
    <p
        class="small text-muted"
    >
        No real card details are collected and no money is charged.
    </p>

    <div
        class="bg-light rounded-3 p-3 small mb-3"
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
            class="form-check mb-3"
        >
            <input
                id="checkout-terms"
                name="terms"
                value="1"
                type="checkbox"
                class="form-check-input"
                required
                @checked(old('terms'))
            >
            <label
                for="checkout-terms"
                class="form-check-label small"
            >
                I understand and agree to the demo checkout terms above.
            </label>
        </div>
        <button
            type="submit"
            class="btn btn-primary w-100 py-3 fw-semibold"
        >
            Create demo payment — ${{ number_format((float) $order->total_amount, 2) }}
        </button>
    @else
        <p
            class="small text-muted"
            role="status"
        >
            {{ $order->status === \App\Enums\OrderStatus::Paid ? 'This order has been paid.' : 'Payment is unavailable. The order may be closed, have a payment in progress, or need a shipping address.' }}
        </p>
        <button
            type="button"
            class="btn btn-secondary w-100 py-3"
            disabled
        >
            Payment unavailable
        </button>
    @endif
</form>
