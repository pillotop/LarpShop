@extends('layouts.app')

@section('title', __('checkout.payment.confirm'))

@section('content')
    <main class="container py-5">
        <h1>{{ __('checkout.payment.confirm') }}</h1>
        <p>{{ __('checkout.payment.order_amount', ['order' => $payment->order_id, 'currency' => $payment->currency, 'amount' => $payment->amount]) }}</p>
        <p>{{ __('checkout.payment.confirm_help') }}</p>
        @if ($errors->any())
            <div role="alert">
                {{ $errors->first() }}
            </div>
        @endif
        <form
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
    </main>
@endsection
