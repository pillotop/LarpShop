<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Quản trị hệ thống') | LarpShop Admin</title>

    {{-- Bootstrap CSS & Icons CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap">

    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #F8F9FA;
            color: #212529;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 260px;
            background: #111424;
            color: #E2E8F0;
            min-height: 100vh;
            transition: all 0.3s;
        }
        .admin-sidebar .nav-link {
            color: #94A3B8;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
            margin-bottom: 0.25rem;
        }
        .admin-sidebar .nav-link:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.08);
        }
        .admin-sidebar .nav-link.active {
            color: #FFFFFF;
            background: #17B0D8;
            box-shadow: 0 4px 12px rgba(23, 176, 216, 0.35);
        }
        .admin-sidebar .brand-title {
            letter-spacing: 0.05em;
        }
        .admin-topbar {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            padding: 0.85rem 1.5rem;
        }
        .metric-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        .table-custom {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }
        .table-custom tr {
            background: #FFFFFF;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            border-radius: 0.75rem;
        }
    </style>
    @yield('head')
</head>
<body>
    <div class="d-flex">
        {{-- Sidebar --}}
        <aside class="admin-sidebar d-flex flex-column flex-shrink-0 p-3">
            <div class="d-flex align-items-center gap-2 px-2 py-3 mb-3 border-bottom border-secondary border-opacity-25">
                <div class="bg-primary bg-gradient rounded-circle p-2 d-flex align-items-center justify-content-center text-white" style="width: 38px; height: 38px;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold brand-title text-white">LarpShop</h5>
                    <small class="text-secondary" style="font-size: 0.75rem;">ADMIN CONSOLE</small>
                </div>
            </div>

            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 fs-5"></i>
                        <span>Bảng điều khiển</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.vendors.index') }}" class="nav-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                        <i class="bi bi-shop fs-5"></i>
                        <span>Quản lý Vendor</span>
                        @php
                            $pendingBadgeCount = \App\Models\Vendor::where('status', 'pending')->count();
                        @endphp
                        @if ($pendingBadgeCount > 0)
                            <span class="badge bg-warning text-dark rounded-pill ms-auto fw-bold">{{ $pendingBadgeCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <small class="text-uppercase text-secondary fw-bold px-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">Lối tắt sàn</small>
                </li>
                <li class="nav-item mt-1">
                    <a href="{{ route('home') }}" class="nav-link" target="_blank">
                        <i class="bi bi-box-arrow-up-right fs-5"></i>
                        <span>Xem trang khách</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('profile') }}" class="nav-link">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span>Hồ sơ Admin</span>
                    </a>
                </li>
            </ul>

            <div class="pt-3 border-top border-secondary border-opacity-25 mt-auto">
                <div class="d-flex align-items-center justify-content-between px-2">
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <i class="bi bi-person-fill-gear text-info fs-4"></i>
                        <div class="text-truncate">
                            <div class="small fw-bold text-white text-truncate">{{ Auth::user()->username ?? 'Admin' }}</div>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Quản trị viên</small>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-2" title="Đăng xuất">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

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

    {{-- Bootstrap Bundle JS CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    @yield('tail')
</body>
</html>
