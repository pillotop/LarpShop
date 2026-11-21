@extends('layouts.app')

@section('title', __('auth.page.title'))

@section('head')
    @vite([
        'resources/css/icons.css',
        'resources/css/components/login_form.css',
        'resources/css/auth_page.css',
        'resources/css/company_footer.css',
    ])
@endsection

@section('tail')
    @vite('resources/js/components/auth_offers.js')
@endsection

@section('content')
    <div class="auth-page">
        @include('auth.partials.header')

        <main class="auth-page-main">
            @include('auth.partials.background')

            <div class="auth-page-content container">
                <section class="auth-page-account" aria-labelledby="login-form-header">
                    <h1 class="visually-hidden">{{ __('auth.page.title') }}</h1>
                    <p class="auth-page-tagline fg-2 mb-4">{{ __('auth.page.tagline') }}</p>
                    <div class="auth-page-form-panel border rounded-2 p-4 sm:p-5">
                        <x-auth.login-form />
                    </div>
                </section>

                @include('auth.partials.offers')
            </div>
        </main>

        <x-company-footer />
    </div>
@endsection
