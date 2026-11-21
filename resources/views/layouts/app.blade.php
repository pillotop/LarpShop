<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('shared.theme-init')
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield("title", "LarpShop")</title>

        {{-- Required for Instrument Sans --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap">

        <!-- Scripts -->
        @vite([
            'resources/scss/app.scss',
            'resources/css/app.css',
            'resources/js/app.js',
        ])

        @yield('head')
        @stack('styles')
    </head>
    <body>

        @include('shared.cart-messages')

        @if ($money->selected() !== 'USD')
            <p class="small text-center border-bottom p-2 mb-0" role="status">
                {{ $money->unavailable() ? __('currency.unavailable') : __('currency.notice') }}
                @unless ($money->unavailable())
                    <a
                        href="https://www.exchangerate-api.com"
                        class="fg-3"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ __('currency.attribution') }}</a>
                @endunless
            </p>
        @endif

        @yield('content')

        {{-- Snap.svg CDN --}}
        <script src="https://cdn.jsdelivr.net/npm/snapsvg@0.5.1/dist/snap.svg-min.min.js"></script>

        @yield('tail')
        @stack('scripts')

    </body>
</html>
