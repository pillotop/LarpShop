@extends('layouts.app')

@section('title', 'Quản lý hình ảnh (UC-32) | ' . $product->name)

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
                <li class="breadcrumb-item"><a href="{{ route('products.show', [$product->product_id, $product->slug]) }}">{{ $product->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý hình ảnh</li>
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
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Product Header Card --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 bg-primary bg-opacity-10 p-3 text-primary">
                        <i class="bi bi-images fs-2"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Quản lý hình ảnh sản phẩm (UC-32)</h4>
                        <div class="text-muted small">
                            Sản phẩm: <strong>{{ $product->name }}</strong> (#{{ $product->product_id }}) &bull;
                            Giá: <span class="text-success fw-bold">${{ number_format($product->price, 2) }}</span> &bull;
                            Kho: <strong>{{ $product->stock }}</strong>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('products.show', [$product->product_id, $product->slug]) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-eye me-1"></i>Xem trang sản phẩm
                    </a>
                </div>
            </div>
        </div>

        {{-- Upload New Images Section --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="border-bottom pb-3 mb-4">
                <h5 class="fw-bold text-dark mb-1">
                    <i class="bi bi-cloud-arrow-up text-primary me-2"></i>Tải lên hình ảnh mới
                </h5>
                <p class="text-muted small mb-0">Hỗ trợ các định dạng JPG, JPEG, PNG, WEBP, GIF (Tối đa 5MB mỗi ảnh. Có thể chọn cùng lúc nhiều ảnh).</p>
            </div>

            <form action="{{ route('vendor.products.images.store', $product->product_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <label for="images" class="form-label fw-semibold">Chọn tập tin ảnh <span class="text-danger">*</span></label>
                        <input
                            type="file"
                            class="form-control form-control-lg rounded-3 @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                            id="images"
                            name="images[]"
                            multiple
                            accept="image/png,image/jpeg,image/webp,image/gif"
                            required
                        >
                        @error('images')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="alt_text" class="form-label fw-semibold">Mô tả ảnh (Alt text)</label>
                        <input
                            type="text"
                            class="form-control form-control-lg rounded-3"
                            id="alt_text"
                            name="alt_text"
                            placeholder="Mô tả ngắn gọn về ảnh..."
                        >
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100 fw-semibold">
                            <i class="bi bi-upload me-1"></i>Tải lên
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Gallery Grid Section --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom pb-3 mb-4">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-grid-3x3 text-primary me-2"></i>Danh sách hình ảnh hiện có ({{ $images->count() }})
                </h5>
                <span class="text-muted small">Ảnh có thứ tự nhỏ nhất sẽ tự động được chọn làm ảnh đại diện chính.</span>
            </div>

            @if ($images->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-image fs-1 d-block mb-3 text-secondary"></i>
                    <h6 class="fw-bold">Chưa có hình ảnh nào cho sản phẩm này</h6>
                    <p class="small text-muted mb-0">Hãy tải lên những bức ảnh đẹp nhất để thu hút khách hàng mua sản phẩm.</p>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($images as $index => $img)
                        <x-vendor.image-card
                            :image="$img"
                            :product="$product"
                            :is-primary="$img->sort_order === 1 || ($loop->first && ! $images->contains('sort_order', 1))"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
