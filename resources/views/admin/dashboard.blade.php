@extends('layouts.admin')

@section('title', 'Bảng điều khiển quản trị (Dashboard)')
@section('page_title', 'Bảng điều khiển quản trị')

@section('content')
<div class="container-fluid p-0">
    {{-- Metric Stat Cards (UC-38) --}}
    <div class="row g-4 mb-4">
        {{-- Pending Vendor Applications (Highlighted) --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card metric-card h-100 border-start border-warning border-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Đơn Vendor chờ duyệt</span>
                        <h2 class="display-6 fw-bold text-warning-emphasis mb-0 mt-1">{{ $metrics['pending_vendors'] }}</h2>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">Cần xử lý phê duyệt</small>
                    <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="small text-decoration-none fw-bold text-warning-emphasis">
                        Xử lý ngay <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Active Vendors --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card metric-card h-100 border-start border-success border-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Gian hàng hoạt động</span>
                        <h2 class="display-6 fw-bold text-success mb-0 mt-1">{{ $metrics['total_vendors'] }}</h2>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                        <i class="bi bi-shop fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">Đang mở bán trên sàn</small>
                    <a href="{{ route('admin.vendors.index', ['status' => 'approved']) }}" class="small text-decoration-none fw-bold text-success">
                        Xem danh sách <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Total Customers --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card metric-card h-100 border-start border-primary border-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Tổng Khách hàng</span>
                        <h2 class="display-6 fw-bold text-primary mb-0 mt-1">{{ $metrics['total_customers'] }}</h2>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top">
                    <small class="text-muted">Tổng {{ $metrics['total_users'] }} tài khoản thành viên</small>
                </div>
            </div>
        </div>

        {{-- Total Products --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card metric-card h-100 border-start border-info border-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Sản phẩm niêm yết</span>
                        <h2 class="display-6 fw-bold text-info mb-0 mt-1">{{ $metrics['total_products'] }}</h2>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                        <i class="bi bi-box-seam-fill fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top">
                    <small class="text-muted">Đạo cụ, giáp trụ & phục trang LARP</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Row: Pending Vendor Registration Queue (UC-40 from Dashboard) --}}
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom flex-wrap gap-2">
                    <div>
                        <h2 class="h5 fw-bold text-dark mb-1">
                            <i class="bi bi-clock-history text-warning me-2"></i>Đơn đăng ký Vendor chờ duyệt
                        </h2>
                        <small class="text-muted">Danh sách người dùng đăng ký mở shop cần xét duyệt để kích hoạt quyền bán hàng.</small>
                    </div>
                    <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        Xem tất cả ({{ $metrics['pending_vendors'] }})
                    </a>
                </div>

                @if ($pendingVendorRequests->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check2-circle text-success" style="font-size: 3rem;"></i>
                        <h5 class="fw-bold mt-3 text-dark">Hiện không có đơn nào đang chờ duyệt!</h5>
                        <p class="small text-muted mb-0">Tất cả đơn đăng ký mở gian hàng đã được xử lý.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th>Gian hàng đăng ký</th>
                                    <th>Người nộp đơn</th>
                                    <th>Thời gian gửi</th>
                                    <th class="text-end">Hành động duyệt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingVendorRequests as $request)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $request->store_name }}</div>
                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                                {{ $request->description ?: 'Không có mô tả' }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $request->user->username ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $request->user->email ?? '' }}</small>
                                        </td>
                                        <td>
                                            <span class="small text-secondary">
                                                {{ $request->created_at ? $request->created_at->format('d/m/Y H:i') : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                {{-- Approve Button --}}
                                                <form action="{{ route('admin.vendors.approve', $request->vendor_id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold shadow-sm" title="Phê duyệt đơn">
                                                        <i class="bi bi-check-lg me-1"></i> Duyệt
                                                    </button>
                                                </form>

                                                {{-- Reject Button --}}
                                                <form action="{{ route('admin.vendors.reject', $request->vendor_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn từ chối đơn này không?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Từ chối đơn">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Side Column: Recent Active Vendors --}}
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <h2 class="h6 fw-bold text-dark mb-0">
                        <i class="bi bi-patch-check-fill text-success me-2"></i>Gian hàng mới kích hoạt
                    </h2>
                    <a href="{{ route('admin.vendors.index', ['status' => 'approved']) }}" class="small text-decoration-none">Xem tất cả</a>
                </div>

                @if ($recentVendors->isEmpty())
                    <p class="text-muted small py-4 text-center mb-0">Chưa có gian hàng nào đang hoạt động.</p>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($recentVendors as $v)
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $v->store_name }}</div>
                                        <small class="text-muted">Chủ shop: {{ $v->user->username ?? 'N/A' }}</small>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        Active
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
