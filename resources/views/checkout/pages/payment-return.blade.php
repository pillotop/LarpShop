@extends('layouts.checkout')

@section('title', __('checkout.payment.confirm'))

@section('payment-content')
    <section class="payment-focus payment-surface p-3 md:p-4">
        <h1 class="h3 mb-3">{{ __('checkout.payment.confirm') }}</h1>
        <p>{{ __('checkout.payment.order_amount', ['order' => $payment->order_id, 'currency' => $payment->currency, 'amount' => $payment->amount]) }}</p>
        <p>{{ __('checkout.payment.confirm_help') }}</p>

        <form
            class="my-4"
            action="{{ route('payments.complete', $payment) }}"
            method="POST"
        >
            @csrf
            <button
                type="submit"
                class="btn btn-solid theme-primary"
            >
                {{ __('checkout.payment.confirm') }}
            </button>
        </form>
        <a href="{{ route('checkout.show', $payment->order_id) }}">{{ __('checkout.payment.back') }}</a>
    </section>
@endsection
