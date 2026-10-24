@extends('layouts.app')

@section('title', 'Cấu hình gian hàng (UC-29) | ' . $vendor->store_name)

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
    <script src="/js/components/side_panel.js"></script>
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
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile') }}">Hồ sơ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cấu hình gian hàng</li>
            </ol>
        </nav>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div class="flex-grow-1">
                    <strong>Thành công!</strong> {{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-4 p-3 mb-4 shadow-sm" role="alert">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                    <strong>Vui lòng kiểm tra lại thông tin:</strong>
                </div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            {{-- Sidebar / Store Overview --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white mb-4">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-shop fs-1 text-primary"></i>
                    </div>
                    <h5 class="fw-bold mb-1">{{ $vendor->store_name }}</h5>
                    <div class="mb-3">
                        @if ($vendor->status === 'active' || $vendor->status === 'approved')
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                <i class="bi bi-check-circle me-1"></i>Đang hoạt động
                            </span>
                        @elseif ($vendor->status === 'pending')
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1">
                                <i class="bi bi-clock-history me-1"></i>Đang chờ duyệt
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1">
                                {{ ucfirst($vendor->status) }}
                            </span>
                        @endif
                    </div>

                    <div class="d-flex justify-content-around py-3 border-top border-bottom mb-3 text-start">
                        <div>
                            <small class="text-muted d-block">Sản phẩm</small>
                            <span class="fw-bold fs-5">{{ $vendor->products_count ?? 0 }}</span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Mã gian hàng</small>
                            <span class="fw-bold fs-5">#{{ $vendor->vendor_id }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('vendor.products.index') }}" class="btn btn-primary rounded-pill">
                            <i class="bi bi-box-seam me-1"></i>Sản phẩm của tôi (UC-32)
                        </a>
                        <a href="{{ route('vendors.show', $vendor->vendor_id) }}" class="btn btn-outline-primary rounded-pill" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Xem trang công khai
                        </a>
                        <a href="{{ route('vendor.categories.index') }}" class="btn btn-light rounded-pill">
                            <i class="bi bi-tags me-1"></i>Quản lý danh mục (UC-30/31)
                        </a>
                    </div>
                </div>
            </div>

            {{-- Main Form / Store Configuration --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="border-bottom pb-3 mb-4">
                        <h4 class="fw-bold text-dark mb-1">
                            <i class="bi bi-gear-wide-connected text-primary me-2"></i>Cấu hình cửa hàng (UC-29)
                        </h4>
                        <p class="text-muted small mb-0">Cập nhật thông tin giới thiệu, thương hiệu và mô tả hoạt động của gian hàng.</p>
                    </div>

                    <form action="{{ route('vendor.store.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="store_name" class="form-label fw-semibold">Tên gian hàng / Thương hiệu <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control form-control-lg rounded-3 @error('store_name') is-invalid @enderror"
                                id="store_name"
                                name="store_name"
                                value="{{ old('store_name', $vendor->store_name) }}"
                                required
                            >
                            @error('store_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Tên gian hàng là duy nhất và sẽ hiển thị trên tất cả sản phẩm của bạn.</div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">Mô tả gian hàng</label>
                            <textarea
                                class="form-control rounded-3 @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="5"
                                placeholder="Hãy kể về câu chuyện thương hiệu, cam kết chất lượng hoặc các chính sách bảo hành, giao hàng của bạn..."
                            >{{ old('description', $vendor->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ route('profile') }}" class="btn btn-light rounded-pill px-4">
                                <i class="bi bi-arrow-left me-1"></i>Về trang hồ sơ
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                                <i class="bi bi-save me-1"></i>Lưu thay đổi cấu hình
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
