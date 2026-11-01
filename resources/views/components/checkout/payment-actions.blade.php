@props(['payment'])

<div class="mt-3 d-grid gap-3">
    @if ($payment->provider_reference === null || ($payment->checkout_url === null && config("payments.providers.{$payment->provider}.redirect", false)))
        @if ($payment->status === \App\Enums\PaymentStatus::Pending)
            <form
                action="{{ route('payments.retry', $payment) }}"
                method="POST"
            >
                @csrf
                <button
                    type="submit"
                    class="btn btn-solid theme-primary"
                >
                    {{ __('checkout.payment.retry') }}
                </button>
            </form>
        @endif
    @elseif ($payment->provider === 'fake' && Route::has('test.payments.simulate') && $payment->status === \App\Enums\PaymentStatus::Pending)
        <form
            action="{{ route('test.payments.simulate', $payment) }}"
            method="POST"
        >
            @csrf
            <label for="fake-outcome">{{ __('checkout.payment.outcome') }}</label>
            <select
                id="fake-outcome"
                name="outcome"
                class="form-control mb-3"
            >
                <option value="succeeded">{{ __('checkout.payment_status.succeeded') }}</option>
                <option value="failed">{{ __('checkout.payment_status.failed') }}</option>
                <option value="cancelled">{{ __('checkout.payment_status.cancelled') }}</option>
                <option value="delayed">{{ __('checkout.payment_status.delayed') }}</option>
            </select>
            <button
                type="submit"
                class="btn btn-solid theme-primary"
            >
                {{ __('checkout.payment.simulate') }}
            </button>
        </form>
    @endif

    @if ($payment->provider_reference !== null && in_array($payment->status, [\App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::Processing], true))
        <form
            action="{{ route('payments.complete', $payment) }}"
            method="POST"
        >
            @csrf
            <button
                type="submit"
                class="btn btn-outline theme-primary"
            >
                {{ __('checkout.payment.check') }}
            </button>
        </form>
    @endif
</div>
