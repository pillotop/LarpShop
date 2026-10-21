@props([
    'user',
    'isVendor' => false,
    'vendor' => null,
])

<section class="profile-hero p-4 p-md-5 mb-4 shadow">
    <div class="row align-items-center g-4">
        <div class="col-auto">
            <div class="avatar-circle">
                {{ strtoupper(substr($user->username, 0, 1)) }}
            </div>
        </div>
        <div class="col">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h1 class="h2 fw-bold mb-0">{{ $user->username }}</h1>
                @if ($user->role === 'admin')
                    <span class="badge bg-danger text-white px-3 py-1 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-shield-lock-fill me-1"></i> Quản trị viên (Admin)
                    </span>
                @elseif ($isVendor)
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-semibold">
                        <i class="bi bi-patch-check-fill me-1"></i> Người bán (Vendor)
                    </span>
                @else
                    <span class="badge bg-light text-primary px-3 py-1 rounded-pill fw-semibold">
                        <i class="bi bi-person-fill me-1"></i> Khách hàng (Customer)
                    </span>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-3 text-white-50 mt-2 small">
                <div>
                    <i class="bi bi-envelope me-1 text-white"></i> {{ $user->email }}
                </div>
                @if ($user->phone_number)
                    <div>
                        <i class="bi bi-telephone me-1 text-white"></i> {{ $user->phone_number }}
                    </div>
                @endif
                <div>
                    <i class="bi bi-calendar-event me-1 text-white"></i> Thành viên từ: {{ $user->created_at ? $user->created_at->format('d/m/Y') : 'Mới tham gia' }}
                </div>
            </div>
        </div>
        @if ($user->role === 'admin')
            <div class="col-12 col-md-auto">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-light rounded-pill px-4 py-2 fw-semibold text-danger shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-speedometer2"></i>
                    <span>Trang Quản trị hệ thống</span>
                </a>
            </div>
        @elseif ($isVendor && $vendor && in_array($vendor->status, ['active', 'approved']))
            <div class="col-12 col-md-auto">
                <a href="{{ route('vendors.show', $vendor->vendor_id) }}" class="btn btn-light rounded-pill px-4 py-2 fw-semibold text-primary shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-shop"></i>
                    <span>Xem gian hàng công khai</span>
                </a>
            </div>
        @endif
    </div>
</section>
