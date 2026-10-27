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
                    class="btn btn-primary"
                >
                    Retry payment setup
                </button>
            </form>
        @endif
    @elseif ($payment->provider === 'fake' && Route::has('test.payments.simulate') && $payment->status === \App\Enums\PaymentStatus::Pending)
        <form
            action="{{ route('test.payments.simulate', $payment) }}"
            method="POST"
        >
            @csrf
            <label for="fake-outcome">Simulated outcome</label>
            <select
                id="fake-outcome"
                name="outcome"
                class="form-select mb-3"
            >
                <option value="succeeded">Succeeded</option>
                <option value="failed">Failed</option>
                <option value="cancelled">Cancelled</option>
                <option value="delayed">Delayed</option>
            </select>
            <button
                type="submit"
                class="btn btn-primary"
            >
                Simulate payment
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
                class="btn btn-outline-primary"
            >
                Check / complete payment
            </button>
        </form>
    @endif
</div>
