@extends('layouts.app')

@section('title', __('checkout.wallet.simulation_title', ['provider' => config("payments.providers.{$payment->provider}.label")]))

@section('content')
    <main class="container py-5">
        <section class="mx-auto border rounded-2 p-4" style="max-width: 36rem;">
            <p class="small text-body-secondary mb-2">LarpShop · {{ __('checkout.wallet.simulated') }}</p>
            <h1 class="h3 mb-3">
                {{ __('checkout.wallet.simulation_title', ['provider' => config("payments.providers.{$payment->provider}.label")]) }}
            </h1>
            <p role="note">{{ __('checkout.wallet.simulation_notice') }}</p>
            <p class="fw-semibold">
                {{ __('checkout.payment.order_amount', ['order' => $payment->order_id, 'currency' => $payment->currency, 'amount' => $payment->amount]) }}
            </p>

            @if ($errors->any())
                <div class="text-danger mb-3" role="alert">{{ $errors->first() }}</div>
            @endif

            <form
                action="{{ route('payments.wallet-demo.store', $payment) }}"
                method="POST"
                class="d-grid gap-3"
            >
                @csrf
                <label for="wallet-demo-outcome">{{ __('checkout.payment.outcome') }}</label>
                <select
                    id="wallet-demo-outcome"
                    name="outcome"
                    class="form-control"
                    required
                >
                    @foreach (\App\Enums\FakePaymentOutcome::cases() as $outcome)
                        <option
                            value="{{ $outcome->value }}"
                            @selected(old('outcome', 'succeeded') === $outcome->value)
                        >
                            {{ __('checkout.payment_status.'.$outcome->value) }}
                        </option>
                    @endforeach
                </select>
                <button
                    type="submit"
                    class="btn btn-solid theme-primary rounded-2"
                >
                    {{ __('checkout.payment.simulate') }}
                </button>
            </form>

            <a
                href="{{ route('checkout.show', $payment->order_id) }}"
                class="d-inline-block mt-3"
            >
                {{ __('checkout.payment.back') }}
            </a>
        </section>
    </main>
@endsection
