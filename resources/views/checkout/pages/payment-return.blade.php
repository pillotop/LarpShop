@extends('layouts.app')

@section('title', 'Confirm payment')

@section('content')
    <main class="container py-5">
        <h1>Confirm payment</h1>
        <p>Order #{{ $payment->order_id }} — {{ $payment->currency }} {{ $payment->amount }}</p>
        <p>We will check your provider and complete the payment if it has been approved.</p>
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
                class="btn btn-primary"
            >
                Confirm payment
            </button>
        </form>
        <a href="{{ route('checkout.show', $payment->order_id) }}">Back to checkout</a>
    </main>
@endsection
