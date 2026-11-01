@props([
    'metrics' => [],
])

<div class="row g-4 mb-4">
    {{-- Pending Vendor Applications (Highlighted) --}}
    <div class="col-12 sm:col-6 xl:col-3">
        <div class="card metric-card h-100 border-start border-warning border-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="fg-3 small fw-semibold text-uppercase">Đơn Vendor chờ duyệt</span>
                    <h2 class="display-6 fw-bold fg-emphasis-warning mb-0 mt-1">{{ $metrics['pending_vendors'] ?? 0 }}</h2>
                </div>
                <div class="rounded-circle bg-warning bg-10 fg-warning p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                    <i class="bi bi-hourglass-split fs-xl"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                <small class="fg-3">Cần xử lý phê duyệt</small>
                <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="small text-decoration-none fw-bold fg-emphasis-warning">
                    Xử lý ngay <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Active Vendors --}}
    <div class="col-12 sm:col-6 xl:col-3">
        <div class="card metric-card h-100 border-start border-success border-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="fg-3 small fw-semibold text-uppercase">Gian hàng hoạt động</span>
                    <h2 class="display-6 fw-bold fg-success mb-0 mt-1">{{ $metrics['total_vendors'] ?? 0 }}</h2>
                </div>
                <div class="rounded-circle bg-success bg-10 fg-success p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                    <i class="bi bi-shop fs-xl"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                <small class="fg-3">Đang mở bán trên sàn</small>
                <a href="{{ route('admin.vendors.index', ['status' => 'approved']) }}" class="small text-decoration-none fw-bold fg-success">
                    Xem danh sách <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Total Customers --}}
    <div class="col-12 sm:col-6 xl:col-3">
        <div class="card metric-card h-100 border-start border-primary border-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="fg-3 small fw-semibold text-uppercase">Tổng Khách hàng</span>
                    <h2 class="display-6 fw-bold fg-primary mb-0 mt-1">{{ $metrics['total_customers'] ?? 0 }}</h2>
                </div>
                <div class="rounded-circle bg-primary bg-10 fg-primary p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                    <i class="bi bi-people-fill fs-xl"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <small class="fg-3">Tổng {{ $metrics['total_users'] ?? 0 }} tài khoản thành viên</small>
            </div>
        </div>
    </div>

    {{-- Total Products --}}
    <div class="col-12 sm:col-6 xl:col-3">
        <div class="card metric-card h-100 border-start border-info border-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="fg-3 small fw-semibold text-uppercase">Sản phẩm niêm yết</span>
                    <h2 class="display-6 fw-bold fg-info mb-0 mt-1">{{ $metrics['total_products'] ?? 0 }}</h2>
                </div>
                <div class="rounded-circle bg-info bg-10 fg-info p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                    <i class="bi bi-box-seam-fill fs-xl"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <small class="fg-3">Đạo cụ, giáp trụ & phục trang LARP</small>
            </div>
        </div>
    </div>
</div>
