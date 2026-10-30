@extends('layouts.app')

@section('title', 'Details')

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/profile.css',
        'resources/css/top_header.css',
        'resources/css/components/payment_method_container.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/payment_methods.js',
        'resources/js/components/side_panel.js',
    ])
@endsection

@section('content')
    <header>
        <nav class="navigation-bar navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img src="/images/larp_shop.jpg" alt="LarpShop home" class="rounded-circle logo-larpshop-logo">
                </a>
                <div class="d-flex flex-grow-1">
                    <div class="navbar-nav me-auto mb-2 mb-lg-0">
                        <button
                            type="button"
                            class="side-panel-trigger nav-link"
                            data-side-panel-target="#sm-cart"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#side-panel"
                        >
                            Your order
                        </button>
                        <template id="sm-cart">
                            <div class="p-3 text-end">
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                            </div>
                            <x-checkout.order-summary :data="$order"/>
                        </template>
                    </div>
                </div>
                <button
                    type="button"
                    class="side-panel-trigger nav-link d-flex align-items-center gap-2"
                    data-side-panel-target="#settings"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#side-panel"
                >

                    @php
                       $user = Auth::user();
                    @endphp

                    <div class="avatar-circle" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($user?->username ?? 'Guest', 0, 1)) }}
                    </div>

                    {{ $user?->username ?? 'Guest' }}

                </button>

                <template id="settings">
                    <x-user-settings/>
                </template>

            </div>
        </nav>
    </header>
    <main>
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="row g-3">
                <div class="col-12 col-lg-7 d-flex flex-column">
                    <div class="p-4">
                        <x-checkout.details-section :user="$user" :order="$order"/>
                    </div>
                    <div class="p-4">
                        <x-checkout.address-section :order="$order" :addresses="$addresses" :can-change-address="$canChangeAddress"/>
                    </div>
                    <div class="p-4">
                        <x-checkout.payment-section
                            :order="$order"
                            :active-payment="$activePayment"
                            :can-create-payment="$canCreatePayment"
                            :can-resume-payment="$canResumePayment"
                        />
                    </div>
                </div>
                <div class="d-none d-lg-block col-5">
                    <div class="sticky-top pt-4">
                    <x-checkout.order-summary :data="$order"/>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <x-side-panel alignment="end" />
@endsection
