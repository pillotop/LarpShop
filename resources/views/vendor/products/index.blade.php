@extends('layouts.app')

@section('title', 'Sản phẩm của gian hàng | ' . $vendor->store_name)

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
                <li class="breadcrumb-item"><a href="{{ route('vendor.store.settings') }}">Gian hàng</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sản phẩm của tôi</li>
            </ol>
        </nav>

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="bi bi-box-seam text-primary me-2"></i>Sản phẩm của gian hàng: {{ $vendor->store_name }}
                    </h3>
                    <p class="text-muted small mb-0">Quản lý các mặt hàng do chính gian hàng của bạn đăng bán.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('vendor.categories.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-tags me-1"></i>Quản lý danh mục (UC-30/31)
                    </a>
                    <a href="{{ route('vendor.store.settings') }}" class="btn btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-gear me-1"></i>Cấu hình gian hàng (UC-29)
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 80px;">Ảnh</th>
                            <th scope="col">Tên sản phẩm</th>
                            <th scope="col">Danh mục</th>
                            <th scope="col">Giá bán</th>
                            <th scope="col">Kho</th>
                            <th scope="col" class="text-end" style="width: 280px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $primaryImage = $product->images->first();
                            @endphp
                            <tr>
                                <td>
                                    <div class="ratio ratio-1x1 rounded-3 overflow-hidden bg-light" style="width: 60px;">
                                        @if ($primaryImage)
                                            <img src="{{ $primaryImage->url }}" class="object-fit-cover w-100 h-100" alt="{{ $product->name }}">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center text-muted">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                    <small class="text-muted">Mã SP: #{{ $product->product_id }} &bull; {{ $product->images->count() }} ảnh</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $product->category?->name ?? 'Không phân loại' }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-success">
                                    ${{ number_format($product->price, 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $product->stock > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill px-3 py-1">
                                        {{ $product->stock }} chiếc
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        {{-- UC-32: Manage Images Button --}}
                                        <a href="{{ route('vendor.products.images.index', $product->product_id) }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" title="Quản lý hình ảnh">
                                            <i class="bi bi-images me-1"></i>Hình ảnh (UC-32)
                                        </a>

                                        {{-- View Public Product --}}
                                        <a href="{{ route('products.show', [$product->product_id, $product->slug]) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2" title="Xem trên sàn">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-box2 fs-1 d-block mb-3 text-secondary"></i>
                                    Gian hàng của bạn chưa có sản phẩm nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="mt-4 pt-3 border-top">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
