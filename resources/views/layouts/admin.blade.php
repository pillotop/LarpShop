<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('common.admin_title')) | LarpShop Admin</title>

    @vite([
        'resources/scss/app.scss',
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap">

    @vite('resources/css/admin.css')

    @yield('head')
</head>
<body>
    @include('shared.cart-messages')
    @php
        $pendingBadgeCount = \App\Models\Vendor::where('status', 'pending')->count();
    @endphp
    <div class="d-flex">
        {{-- Sidebar --}}
        <x-side-panel :pending-badge-count="$pendingBadgeCount" />

        {{-- Main Content Area --}}
        <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
            {{-- Top Navbar --}}
            <header class="admin-topbar d-flex align-items-center justify-content-between sticky-top">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="mb-0 fw-bold fg-body">@yield('page_title', __('common.admin_title'))</h5>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <button
                        type="button"
                        class="btn btn-outline theme-secondary btn-sm"
                        data-bs-toggle="drawer"
                        data-bs-target="#settings-panel"
                        aria-controls="settings-panel"
                    >
                        <i class="bi bi-gear me-1" aria-hidden="true"></i>
                        {{ __('common.settings') }}
                    </button>
                    <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="btn btn-solid theme-secondary rounded-pill px-3 py-1 position-relative border btn-sm">
                        <i class="bi bi-bell-fill fg-warning me-1"></i>
                        <span>{{ __('common.pending_applications') }}</span>
                        @if ($pendingBadgeCount > 0)
                            <span class="badge bg-danger rounded-pill ms-1">{{ $pendingBadgeCount }}</span>
                        @endif
                    </a>
                </div>
            </header>

            {{-- Main Body --}}
            <main class="p-4 md:p-5">
                {{-- Flash Notifications --}}
                @if (session('success'))
                    <div class="alert theme-success alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm border-0" role="alert">
                        <i class="bi bi-check-circle-fill fs-lg fg-success"></i>
                        <div class="flex-grow-1 fw-semibold">{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('common.close') }}"></button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert theme-warning alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm border-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-lg fg-warning"></i>
                        <div class="flex-grow-1 fw-semibold">{{ session('warning') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('common.close') }}"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert theme-danger alert-dismissible fade show rounded-9 p-3 mb-4 shadow-sm border-0" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-exclamation-octagon-fill fs-lg fg-danger"></i>
                            <strong class="fw-bold">{{ __('common.errors_heading') }}</strong>
                        </div>
                        <ul class="mb-0 ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('common.close') }}"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <x-locale-panels />

    @yield('tail')
</body>
</html>
