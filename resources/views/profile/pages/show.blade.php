@extends('layouts.app')

@section('title', 'Hồ sơ tài khoản | LarpShop')

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
    <link rel="stylesheet" href="/css/profile.css">
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
    <script src="/js/components/side_panel.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash;
            if (hash) {
                const triggerEl = document.querySelector(`[data-bs-target="${hash}"]`);
                if (triggerEl) {
                    bootstrap.Tab.getOrCreateInstance(triggerEl).show();
                }
            } else if (@json($errors->has('store_name') || $errors->has('description') || session('status') === 'vendor-registered' || session('status') === 'vendor-updated')) {
                const vendorTab = document.getElementById('tab-vendor-info');
                if (vendorTab) {
                    bootstrap.Tab.getOrCreateInstance(vendorTab).show();
                }
            } else if (@json($errors->has('current_password') || $errors->has('password') || session('status') === 'password-updated')) {
                const secTab = document.getElementById('tab-security');
                if (secTab) {
                    bootstrap.Tab.getOrCreateInstance(secTab).show();
                }
            }
        });
    </script>
@endsection

@section('content')
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

    <header class="container sticky-top" style="background-color: #ffffff99;">
        <link rel="stylesheet" href="/css/top_header.css">
        <link rel="stylesheet" href="/css/components/navigation_bar.css">
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="offcanvas"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        {{-- Breadcrumbs Navigation --}}
        <nav aria-label="breadcrumb" class="mb-4">
            {{ Breadcrumbs::render('profile') }}
        </nav>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div class="flex-grow-1">
                    <span class="fw-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                <div class="flex-grow-1">
                    <span class="fw-semibold">{{ session('warning') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill fs-4 text-danger"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">Vui lòng kiểm tra lại thông tin:</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Profile Hero Card --}}
        <x-profile.hero-card :user="$user" :is-vendor="$isVendor" :vendor="$vendor" />

        {{-- Navigation Tabs --}}
        <div class="row g-4">
            <div class="col-12 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 sticky-top" style="top: 100px;">
                    <ul class="nav nav-pills flex-column profile-nav-tabs gap-2" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link w-100 text-start d-flex align-items-center gap-2 active"
                                id="tab-customer-info"
                                data-bs-toggle="pill"
                                data-bs-target="#pane-customer"
                                type="button"
                                role="tab"
                                aria-controls="pane-customer"
                                aria-selected="true"
                            >
                                <i class="bi bi-person-lines-fill fs-5"></i>
                                <span>Hồ sơ cá nhân</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link w-100 text-start d-flex align-items-center gap-2"
                                id="tab-vendor-info"
                                data-bs-toggle="pill"
                                data-bs-target="#pane-vendor"
                                type="button"
                                role="tab"
                                aria-controls="pane-vendor"
                                aria-selected="false"
                            >
                                <i class="bi bi-shop-window fs-5"></i>
                                <span>{{ $isVendor ? 'Hồ sơ gian hàng' : 'Đăng ký Người bán' }}</span>
                                @if ($isVendor)
                                    <span class="badge bg-warning text-dark ms-auto">Vendor</span>
                                @endif
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link w-100 text-start d-flex align-items-center gap-2"
                                id="tab-security"
                                data-bs-toggle="pill"
                                data-bs-target="#pane-security"
                                type="button"
                                role="tab"
                                aria-controls="pane-security"
                                aria-selected="false"
                            >
                                <i class="bi bi-shield-lock fs-5"></i>
                                <span>Đổi mật khẩu</span>
                            </button>
                        </li>
                    </ul>

                    <hr class="my-3 text-muted">

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100 rounded-pill py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Đăng xuất</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-9">
                <div class="tab-content" id="profileTabContent">
                    {{-- Tab 1: Customer Personal Profile --}}
                    <div class="tab-pane fade show active" id="pane-customer" role="tabpanel" aria-labelledby="tab-customer-info">
                        {{-- Quick Stats / Info Cards --}}
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6 col-md-4">
                                <div class="stat-card p-3 h-100">
                                    <div class="text-muted small fw-semibold">Tên đăng nhập</div>
                                    <div class="fs-5 fw-bold text-dark mt-1 text-truncate">{{ $user->username }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="stat-card p-3 h-100">
                                    <div class="text-muted small fw-semibold">Email tài khoản</div>
                                    <div class="fs-5 fw-bold text-dark mt-1 text-truncate">{{ $user->email }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="stat-card p-3 h-100">
                                    <div class="text-muted small fw-semibold">Số điện thoại</div>
                                    <div class="fs-5 fw-bold text-dark mt-1 text-truncate">{{ $user->phone_number ?: 'Chưa cập nhật' }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Update Profile Form --}}
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                                <div>
                                    <h2 class="h5 fw-bold mb-1 text-dark">Cập nhật thông tin cá nhân</h2>
                                    <p class="text-muted small mb-0">Quản lý tên hiển thị, địa chỉ email và số điện thoại liên lạc của bạn.</p>
                                </div>
                                @if ($user->role === 'admin')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                                        <i class="bi bi-shield-lock-fill me-1"></i> Quản trị viên
                                    </span>
                                @elseif ($isVendor)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2">
                                        <i class="bi bi-patch-check-fill me-1"></i> Người bán
                                    </span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">
                                        <i class="bi bi-person me-1"></i> Khách hàng
                                    </span>
                                @endif
                            </div>

                            <form action="{{ route('profile.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label fw-semibold">Tên người dùng (Username) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-at"></i></span>
                                            <input
                                                type="text"
                                                class="form-control rounded-end-3 @error('username') is-invalid @enderror"
                                                id="username"
                                                name="username"
                                                value="{{ old('username', $user->username) }}"
                                                required
                                                minlength="3"
                                                maxlength="50"
                                            >
                                            @error('username')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-text small">Tên đăng nhập dùng để xác thực và hiển thị trên hệ thống.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold">Địa chỉ Email <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-envelope"></i></span>
                                            <input
                                                type="email"
                                                class="form-control rounded-end-3 @error('email') is-invalid @enderror"
                                                id="email"
                                                name="email"
                                                value="{{ old('email', $user->email) }}"
                                                required
                                                maxlength="100"
                                            >
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-text small">Email chính thức để nhận thông báo đơn hàng và thông tin bảo mật.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone_number" class="form-label fw-semibold">Số điện thoại liên hệ</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-telephone"></i></span>
                                            <input
                                                type="text"
                                                class="form-control rounded-end-3 @error('phone_number') is-invalid @enderror"
                                                id="phone_number"
                                                name="phone_number"
                                                value="{{ old('phone_number', $user->phone_number) }}"
                                                placeholder="Ví dụ: 0912345678"
                                                maxlength="15"
                                            >
                                            @error('phone_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-text small">Số điện thoại dùng cho việc giao nhận và hỗ trợ khách hàng.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Vai trò tài khoản</label>
                                        <input
                                            type="text"
                                            class="form-control rounded-3 bg-light"
                                            value="{{ $user->role === 'admin' ? 'Quản trị viên (Admin)' : ($user->role === 'vendor' ? 'Người bán (Vendor)' : 'Khách hàng (Customer)') }}"
                                            disabled
                                        >
                                        <div class="form-text small">Vai trò được quản lý tự động bởi hệ thống LarpShop.</div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Lưu thay đổi hồ sơ</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Tab 2: Vendor Store Profile / Register --}}
                    <div class="tab-pane fade" id="pane-vendor" role="tabpanel" aria-labelledby="tab-vendor-info">
                        @if ($isVendor && $vendor)
                            {{-- Vendor Store Overview --}}
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6 col-md-4">
                                    <div class="stat-card p-3 h-100">
                                        <div class="text-muted small fw-semibold">Tên gian hàng</div>
                                        <div class="fs-5 fw-bold text-dark mt-1 text-truncate">{{ $vendor->store_name }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <div class="stat-card p-3 h-100">
                                        <div class="text-muted small fw-semibold">Trạng thái gian hàng</div>
                                        <div class="mt-1">
                                            @if (in_array($vendor->status, ['active', 'approved']))
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Đang hoạt động
                                                </span>
                                            @elseif ($vendor->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">
                                                    <i class="bi bi-clock-fill me-1"></i> Chờ duyệt
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1 fw-semibold">
                                                    {{ ucfirst($vendor->status) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <div class="stat-card p-3 h-100">
                                        <div class="text-muted small fw-semibold">Sản phẩm trong gian hàng</div>
                                        <div class="fs-5 fw-bold text-dark mt-1">{{ $vendorProductCount }} sản phẩm</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Vendor Store Update Form --}}
                            <div class="card border-0 shadow-sm rounded-4 p-4">
                                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom flex-wrap gap-2">
                                    <div>
                                        <h2 class="h5 fw-bold mb-1 text-dark">Hồ sơ gian hàng người bán</h2>
                                        <p class="text-muted small mb-0">Thông tin này sẽ hiển thị công khai trên trang storefront của gian hàng bạn.</p>
                                    </div>
                                    @if (in_array($vendor->status, ['active', 'approved']))
                                        <a href="{{ route('vendors.show', $vendor->vendor_id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                            <span>Xem trang cửa hàng</span>
                                        </a>
                                    @endif
                                </div>

                                <form action="{{ route('profile.vendor.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="store_name" class="form-label fw-semibold">Tên gian hàng / Thương hiệu <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-shop"></i></span>
                                                <input
                                                    type="text"
                                                    class="form-control rounded-end-3 @error('store_name') is-invalid @enderror"
                                                    id="store_name"
                                                    name="store_name"
                                                    value="{{ old('store_name', $vendor->store_name) }}"
                                                    required
                                                    minlength="2"
                                                    maxlength="100"
                                                >
                                                @error('store_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-text small">Tên thương hiệu sẽ hiển thị ở đầu trang sản phẩm và storefront.</div>
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label fw-semibold">Mô tả gian hàng</label>
                                            <textarea
                                                class="form-control rounded-3 @error('description') is-invalid @enderror"
                                                id="description"
                                                name="description"
                                                rows="4"
                                                maxlength="1000"
                                                placeholder="Giới thiệu về xưởng chế tác, sản phẩm đặc trưng hoặc phong cách thiết kế của bạn..."
                                            >{{ old('description', $vendor->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text small">Tối đa 1000 ký tự giới thiệu về cửa hàng của bạn.</div>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                        <button type="submit" class="btn btn-warning rounded-pill px-4 py-2 fw-semibold text-dark d-inline-flex align-items-center gap-2 shadow-sm">
                                            <i class="bi bi-shop"></i>
                                            <span>Lưu hồ sơ gian hàng</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @elseif ($vendor && $vendor->status === 'pending')
                            {{-- Vendor Application Pending Approval --}}
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="card-header bg-warning-subtle text-warning-emphasis p-4 border-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-clock-history fs-4 text-warning-emphasis"></i>
                                            <h2 class="h5 fw-bold mb-0">Đơn đăng ký gian hàng đang chờ xét duyệt</h2>
                                        </div>
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold shadow-sm">
                                            <i class="bi bi-hourglass-split me-1"></i> Chờ duyệt (Pending)
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-4 p-md-5">
                                    <div class="alert alert-light border border-warning-subtle rounded-3 p-3 mb-4 d-flex align-items-center gap-3">
                                        <i class="bi bi-info-circle-fill fs-3 text-warning"></i>
                                        <div class="small text-muted">
                                            Đơn đăng ký mở gian hàng của bạn đã được ghi nhận trên hệ thống LarpShop và đang trong hàng đợi chờ Quản trị viên (Admin) xét duyệt.
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3 bg-light border">
                                                <div class="text-muted small fw-semibold">TÊN GIAN HÀNG ĐĂNG KÝ</div>
                                                <div class="fs-5 fw-bold text-dark mt-1">{{ $vendor->store_name }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3 bg-light border">
                                                <div class="text-muted small fw-semibold">THỜI GIAN GỬI ĐƠN</div>
                                                <div class="fs-5 fw-bold text-dark mt-1">{{ $vendor->created_at ? $vendor->created_at->format('d/m/Y H:i') : 'Mới đây' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="p-3 rounded-3 bg-light border">
                                                <div class="text-muted small fw-semibold">MÔ TẢ GIAN HÀNG</div>
                                                <div class="text-secondary mt-1">{{ $vendor->description ?: 'Chưa có mô tả chi tiết.' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3 border-top">
                                        <span class="text-muted small">
                                            <i class="bi bi-shield-check text-success me-1"></i> Hệ thống sẽ tự động cập nhật ngay khi Quản trị viên phê duyệt.
                                        </span>
                                        <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold">
                                            <i class="bi bi-house me-1"></i> Quay về trang chủ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Not a vendor yet or Rejected: Show Application Form --}}
                            @if ($vendor && $vendor->status === 'rejected')
                                <div class="alert alert-danger alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                                    <i class="bi bi-x-circle-fill fs-4 text-danger"></i>
                                    <div>
                                        <strong>Đơn đăng ký trước đó chưa được duyệt:</strong> Bạn có thể điều chỉnh lại tên hoặc mô tả gian hàng phù hợp hơn và gửi lại đơn xét duyệt bên dưới.
                                    </div>
                                </div>
                            @endif

                            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                                <div class="row align-items-center g-4">
                                    <div class="col-12 col-md-7">
                                        <div class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-semibold mb-2">
                                            <i class="bi bi-stars"></i> Cơ hội kinh doanh
                                        </div>
                                        <h2 class="h3 fw-bold text-dark mb-3">Mở gian hàng Vendor trên LarpShop</h2>
                                        <p class="text-muted mb-4">
                                            Đăng ký mở gian hàng để bắt đầu bày bán các trang phục, vũ khí bọt mút an toàn, phụ kiện và giáp trụ LARP đến cộng đồng người chơi trên toàn quốc.
                                        </p>

                                        <div class="d-flex flex-column gap-2 mb-4">
                                            <div class="d-flex align-items-center gap-2 text-dark">
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                <span>Trang cửa hàng (Storefront) mang thương hiệu riêng của bạn</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 text-dark">
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                <span>Dễ dàng đăng tải hình ảnh và cập nhật sản phẩm</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 text-dark">
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                <span>Tiếp cận cộng đồng người mua yêu thích văn hóa LARP</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-5">
                                        <div class="card border border-primary-subtle rounded-4 p-4 bg-light shadow-sm">
                                            <h3 class="h5 fw-bold text-dark mb-3">Đăng ký mở gian hàng</h3>
                                            <form action="{{ route('profile.vendor.register') }}" method="POST">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="reg_store_name" class="form-label fw-semibold small">Tên gian hàng / Shop <span class="text-danger">*</span></label>
                                                    <input
                                                        type="text"
                                                        class="form-control rounded-3 @error('store_name') is-invalid @enderror"
                                                        id="reg_store_name"
                                                        name="store_name"
                                                        value="{{ old('store_name', $vendor?->store_name) }}"
                                                        placeholder="VD: Tiệm Đạo Cụ Hiệp Sĩ"
                                                        required
                                                        minlength="2"
                                                        maxlength="100"
                                                    >
                                                    @error('store_name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-3">
                                                    <label for="reg_description" class="form-label fw-semibold small">Mô tả ngắn về gian hàng</label>
                                                    <textarea
                                                        class="form-control rounded-3 @error('description') is-invalid @enderror"
                                                        id="reg_description"
                                                        name="description"
                                                        rows="3"
                                                        placeholder="Chuyên sản xuất đao kiếm mút, giáp da thủ công..."
                                                        maxlength="1000"
                                                    >{{ old('description', $vendor?->description) }}</textarea>
                                                    @error('description')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 shadow-sm">
                                                    <i class="bi bi-send-fill"></i>
                                                    <span>Gửi đơn đăng ký xét duyệt</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Tab 3: Security & Password Update --}}
                    <div class="tab-pane fade" id="pane-security" role="tabpanel" aria-labelledby="tab-security">
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                                <div>
                                    <h2 class="h5 fw-bold mb-1 text-dark">Thay đổi mật khẩu tài khoản</h2>
                                    <p class="text-muted small mb-0">Đảm bảo mật khẩu của bạn có độ dài tối thiểu 8 ký tự để bảo vệ tài khoản tốt nhất.</p>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2">
                                    <i class="bi bi-shield-lock"></i> Bảo mật
                                </span>
                            </div>

                            <form action="{{ route('profile.password.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-12 col-md-8">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label fw-semibold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-key"></i></span>
                                                <input
                                                    type="password"
                                                    class="form-control rounded-end-3 @error('current_password') is-invalid @enderror"
                                                    id="current_password"
                                                    name="current_password"
                                                    required
                                                    autocomplete="current-password"
                                                >
                                                @error('current_password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label fw-semibold">Mật khẩu mới <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-lock"></i></span>
                                                <input
                                                    type="password"
                                                    class="form-control rounded-end-3 @error('password') is-invalid @enderror"
                                                    id="password"
                                                    name="password"
                                                    required
                                                    minlength="8"
                                                    autocomplete="new-password"
                                                >
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-text small">Tối thiểu 8 ký tự, kết hợp chữ cái và số để tăng tính bảo mật.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password_confirmation" class="form-label fw-semibold">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-lock-fill"></i></span>
                                                <input
                                                    type="password"
                                                    class="form-control rounded-end-3 @error('password_confirmation') is-invalid @enderror"
                                                    id="password_confirmation"
                                                    name="password_confirmation"
                                                    required
                                                    minlength="8"
                                                    autocomplete="new-password"
                                                >
                                                @error('password_confirmation')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3 border-top d-flex justify-content-start">
                                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                                                <i class="bi bi-shield-check"></i>
                                                <span>Cập nhật mật khẩu mới</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <div class="p-3 rounded-4 bg-light border border-light-subtle h-100">
                                            <div class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                                <i class="bi bi-info-circle text-primary"></i> Lời khuyên bảo mật
                                            </div>
                                            <ul class="text-muted small ps-3 mb-0" style="line-height: 1.6;">
                                                <li>Không chia sẻ mật khẩu của bạn với bất kỳ ai.</li>
                                                <li>Nên sử dụng mật khẩu độc nhất không trùng với các trang web khác.</li>
                                                <li>Đổi mật khẩu định kỳ nếu sử dụng thiết bị công cộng.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
