@extends('layouts.app')

@section('title', 'Đăng ký trở thành Vendor - LarpShop')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            {{-- Breadcrumb / Back button --}}
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Đăng ký người bán (Vendor)</li>
                </ol>
            </nav>

            {{-- Flash Alert Messages --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                    <div>{{ session('warning') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
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

            @if ($vendor)
                {{-- User already has a vendor record --}}
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-primary text-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 fw-bold">Thông tin cửa hàng của bạn</h4>
                            @if ($vendor->status === 'approved')
                                <span class="badge bg-success fs-6 px-3 py-2">
                                    <i class="bi bi-patch-check-fill me-1"></i> Đã phê duyệt
                                </span>
                            @elseif ($vendor->status === 'rejected')
                                <span class="badge bg-danger fs-6 px-3 py-2">
                                    <i class="bi bi-x-circle-fill me-1"></i> Bị từ chối
                                </span>
                            @else
                                <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                    <i class="bi bi-hourglass-split me-1"></i> Chờ xét duyệt
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">TÊN CỬA HÀNG</label>
                            <h5 class="fw-bold text-dark">{{ $vendor->store_name }}</h5>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">MÔ TẢ CỬA HÀNG</label>
                            <p class="text-secondary mb-0">
                                {{ $vendor->description ?: 'Chưa có mô tả.' }}
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="text-muted small fw-semibold">NGÀY ĐĂNG KÝ</label>
                            <p class="text-secondary mb-0">
                                {{ $vendor->created_at ? $vendor->created_at->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            @if ($vendor->status === 'approved')
                                <div class="text-success small">
                                    <i class="bi bi-check2-circle me-1"></i> Cửa hàng của bạn đã sẵn sàng để đăng bán sản phẩm.
                                </div>
                                <a href="{{ route('vendor.product.create') }}" class="btn btn-primary px-4 py-2 rounded-pill">
                                    <i class="bi bi-plus-circle me-1"></i> Thêm sản phẩm mới
                                </a>
                            @elseif ($vendor->status === 'pending')
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> Đơn đăng ký của bạn đang được quản trị viên xem xét. Chúng tôi sẽ cập nhật trạng thái sớm nhất.
                                </div>
                                <a href="{{ route('home') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                    Quay lại trang chủ
                                </a>
                            @else
                                <div class="text-danger small">
                                    <i class="bi bi-exclamation-circle me-1"></i> Đơn đăng ký không được chấp thuận. Vui lòng liên hệ bộ phận hỗ trợ.
                                </div>
                                <a href="{{ url('/supports') }}" class="btn btn-outline-danger px-4 py-2 rounded-pill">
                                    Liên hệ hỗ trợ
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                {{-- Registration Form --}}
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-dark text-white p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white text-dark p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-shop-window fs-4"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold">Đăng ký trở thành Người bán (Vendor)</h3>
                                <p class="mb-0 text-white-50">Mở gian hàng riêng của bạn trên LarpShop và tiếp cận hàng ngàn khách hàng tiềm năng</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        {{-- Feature Highlights --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 text-center h-100 bg-light">
                                    <i class="bi bi-box-seam text-primary fs-3 mb-2 d-block"></i>
                                    <h6 class="fw-bold mb-1">Quản lý dễ dàng</h6>
                                    <small class="text-muted">Đăng bán và cập nhật sản phẩm nhanh chóng, linh hoạt.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 text-center h-100 bg-light">
                                    <i class="bi bi-people text-success fs-3 mb-2 d-block"></i>
                                    <h6 class="fw-bold mb-1">Cộng đồng LARP</h6>
                                    <small class="text-muted">Kết nối trực tiếp tới người yêu thích đạo cụ, trang phục LARP.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 text-center h-100 bg-light">
                                    <i class="bi bi-shield-check text-info fs-3 mb-2 d-block"></i>
                                    <h6 class="fw-bold mb-1">Minh bạch & Uy tín</h6>
                                    <small class="text-muted">Hỗ trợ đối soát và kiểm soát đơn hàng minh bạch, an toàn.</small>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('vendor.register.store') }}" method="POST" class="needs-validation">
                            @csrf

                            {{-- Store Name --}}
                            <div class="mb-4">
                                <label for="store_name" class="form-label fw-semibold">
                                    Tên cửa hàng / Thương hiệu <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="store_name"
                                    name="store_name"
                                    value="{{ old('store_name') }}"
                                    class="form-control form-control-lg @error('store_name') is-invalid @enderror"
                                    placeholder="Ví dụ: Larp Armor Workshop, Medieval Costumes..."
                                    required
                                    maxlength="100"
                                >
                                @error('store_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">Tên cửa hàng độc nhất hiển thị cho khách hàng (từ 3 đến 100 ký tự).</div>
                                @enderror
                            </div>

                            {{-- Store Description --}}
                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">
                                    Mô tả gian hàng
                                </label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="4"
                                    class="form-control @error('description') is-invalid @enderror"
                                    placeholder="Giới thiệu về thương hiệu, kinh nghiệm sản xuất, phong cách đạo cụ hoặc các loại mặt hàng bạn dự định cung cấp..."
                                    maxlength="1000"
                                >{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">Mô tả chi tiết giúp đơn đăng ký được duyệt nhanh hơn (tối đa 1000 ký tự).</div>
                                @enderror
                            </div>

                            {{-- Terms & Policy Note --}}
                            <div class="alert alert-light border small text-muted mb-4">
                                <i class="bi bi-info-circle me-1"></i>
                                Bằng cách nhấn <strong>Gửi đơn đăng ký</strong>, bạn xác nhận rằng thông tin cung cấp là chính xác và cam kết tuân thủ quy chế hoạt động của nền tảng LarpShop.
                            </div>

                            {{-- Actions --}}
                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <a href="{{ route('home') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                    Hủy bỏ
                                </a>
                                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-semibold shadow-sm">
                                    <i class="bi bi-send me-1"></i> Gửi đơn đăng ký
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
