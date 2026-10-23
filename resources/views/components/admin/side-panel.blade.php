@props([
    'pendingBadgeCount' => null,
])

@php
    $pendingBadgeCount = $pendingBadgeCount ?? \App\Models\Vendor::where('status', 'pending')->count();
    $pendingProductCount = \App\Models\Product::where('status', 'pending')->count();
@endphp

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
                @if ($pendingBadgeCount > 0)
                    <span class="badge bg-warning text-dark rounded-pill ms-auto fw-bold">{{ $pendingBadgeCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3 fs-5"></i>
                <span>Categories</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people fs-5"></i>
                <span>Users &amp; Roles</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt fs-5"></i>
                <span>Orders &amp; Disputes</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.products.moderation.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam fs-5"></i>
                <span>Product Moderation</span>
                @if ($pendingProductCount > 0)
                    <span class="badge bg-warning text-dark rounded-pill ms-auto fw-bold">{{ $pendingProductCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                <i class="bi bi-chat-square-quote fs-5"></i>
                <span>Review Moderation</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow fs-5"></i>
                <span>Platform Reports</span>
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
