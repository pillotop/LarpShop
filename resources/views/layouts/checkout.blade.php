@extends('layouts.app')

@section('head')
    @vite(['resources/css/checkout.css', 'resources/css/components/payment_method_container.css'])
@endsection

@section('tail')
    @vite('resources/js/components/payment_methods.js')
@endsection

@section('content')
    <header class="payment-header border-bottom">
        <nav class="container d-flex flex-wrap align-items-center justify-content-between gap-3 py-3" aria-label="{{ __('checkout.title') }}">
            <a href="{{ route('home') }}" class="payment-brand" aria-label="{{ __('common.home') }}">
                <svg aria-hidden="true"><use href="{{ asset('images/logo.svg') }}#larpshop-horizontal"></use></svg>
            </a>
            <div class="d-flex align-items-center flex-wrap gap-3">
                <a href="{{ route('profile') }}#{{ \App\Queries\Orders\PurchaseHistoryQuery::FRAGMENT }}">{{ __('orders.history') }}</a>
                <button type="button" class="btn btn-sm btn-outline theme-primary" data-bs-toggle="drawer" data-bs-target="#settings-panel">
                    <i class="bi bi-gear" aria-hidden="true"></i> {{ __('common.settings') }}
                </button>
            </div>
        </nav>
    </header>
    <main class="container payment-page py-4 lg:py-5">
        @if (session('status'))
            <div class="alert theme-primary" role="status">{{ session('status') }}</div>
        @endif
        @if (session('success'))
            <div class="alert theme-primary" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert theme-secondary" role="alert">
                <ul class="mb-0">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('payment-content')
    </main>
    <x-settings-panel />
@endsection
