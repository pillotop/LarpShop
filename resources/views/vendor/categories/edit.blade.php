@extends('layouts.app')

@section('title', 'Chỉnh sửa danh mục (UC-31) | ' . $category->name)

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
                <li class="breadcrumb-item"><a href="{{ route('vendor.categories.index') }}">Danh mục</a></li>
                <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa: {{ $category->name }}</li>
            </ol>
        </nav>

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 border-bottom pb-3 mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Chỉnh sửa danh mục (UC-31)
                    </h3>
                    <p class="text-muted small mb-0">Cập nhật thông tin danh mục <strong>{{ $category->name }}</strong>.</p>
                </div>
                <div>
                    {{-- Toggle active status button --}}
                    <form action="{{ route('vendor.categories.toggle-status', $category->category_id) }}" method="POST" class="m-0" onsubmit="return confirm('Bạn có chắc muốn thay đổi trạng thái danh mục này không?');">
                        @csrf
                        @method('PATCH')
                        @if ($category->is_active)
                            <button type="submit" class="btn btn-outline-danger rounded-pill px-3">
                                <i class="bi bi-slash-circle me-1"></i>Vô hiệu hóa danh mục (UC-31)
                            </button>
                        @else
                            <button type="submit" class="btn btn-outline-success rounded-pill px-3">
                                <i class="bi bi-check-circle me-1"></i>Kích hoạt lại danh mục (UC-31)
                            </button>
                        @endif
                    </form>
                </div>
            </div>

            <x-vendor.category-form
                :category="$category"
                :parent-categories="$parentCategories"
                :action="route('vendor.categories.update', $category->category_id)"
                method="PUT"
                submit-label="Cập nhật danh mục"
            />
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
