@extends('layouts.checkout')

@section('title', __('checkout.title'))

@section('payment-content')
    <div class="mb-4">
        <h1 class="h2 mb-2">{{ __('checkout.title') }}</h1>
        <p class="fg-3 mb-0">{{ __('orders.number', ['id' => $order->getKey()]) }}</p>
    </div>
    <div class="payment-columns">
        <div class="d-grid gap-4">
            <div class="payment-surface p-3 md:p-4">
                <x-checkout.details-section :user="$user" :order="$order" />
            </div>
            <div class="payment-surface p-3 md:p-4">
                <x-checkout.address-section :order="$order" :addresses="$addresses" :can-change-address="$canChangeAddress" />
            </div>
            <div class="payment-surface p-3 md:p-4">
                <x-checkout.payment-section :order="$order" :active-payment="$activePayment" :can-create-payment="$canCreatePayment" :can-resume-payment="$canResumePayment" />
            </div>
        </div>
        <aside class="payment-summary">
            <x-checkout.order-summary :data="$order" />
        </aside>
    </div>
@endsection
