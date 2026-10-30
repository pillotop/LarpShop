<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Quản trị hệ thống') | LarpShop Admin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])



    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap">

    @vite('resources/css/admin.css')

    @yield('head')
</head>
<body>
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
                    <h5 class="mb-0 fw-bold text-dark">@yield('page_title', 'Quản trị hệ thống')</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="btn btn-light rounded-pill px-3 py-1 position-relative border btn-sm">
                        <i class="bi bi-bell-fill text-warning me-1"></i>
                        <span>Đơn chờ duyệt</span>
                        @if ($pendingBadgeCount > 0)
                            <span class="badge bg-danger rounded-pill ms-1">{{ $pendingBadgeCount }}</span>
                        @endif
                    </a>
                </div>
            </header>

            {{-- Main Body --}}
            <main class="p-4 p-md-5">
                {{-- Flash Notifications --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm border-0" role="alert">
                        <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                        <div class="flex-grow-1 fw-semibold">{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm border-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                        <div class="flex-grow-1 fw-semibold">{{ session('warning') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 p-3 mb-4 shadow-sm border-0" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-exclamation-octagon-fill fs-4 text-danger"></i>
                            <strong class="fw-bold">Đã có lỗi xảy ra:</strong>
                        </div>
                        <ul class="mb-0 ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @yield('tail')
</body>
</html>
