@props([
    'vendors',
])

@if ($vendors->isEmpty())
    <div class="text-center py-5">
        <div class="rounded-circle bg-light d-inline-flex p-4 mb-3 text-muted">
            <i class="bi bi-inbox fs-1"></i>
        </div>
        <h5 class="fw-bold text-dark">Không có hồ sơ nào phù hợp với bộ lọc</h5>
        <p class="text-muted small">Hãy chuyển đổi qua các trạng thái khác để xem danh sách gian hàng.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small text-muted">
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Thông tin Gian hàng</th>
                    <th>Chủ tài khoản</th>
                    <th>Thời gian gửi</th>
                    <th>Trạng thái</th>
                    <th class="text-end" style="min-width: 220px;">Hành động quản trị</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vendors as $vendor)
                    <tr>
                        <td class="text-muted fw-bold">#{{ $vendor->vendor_id }}</td>
                        <td>
                            <div class="fw-bold text-dark fs-6">{{ $vendor->store_name }}</div>
                            <small class="text-secondary d-block mt-1" style="max-width: 320px;">
                                {{ $vendor->description ?: 'Chưa có mô tả giới thiệu gian hàng.' }}
                            </small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px;">
                                    {{ strtoupper(substr($vendor->user->username ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">{{ $vendor->user->username ?? 'N/A' }}</div>
                                    <small class="text-muted d-block">{{ $vendor->user->email ?? '' }}</small>
                                    @if ($vendor->user?->phone_number)
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $vendor->user->phone_number }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">
                                {{ $vendor->created_at ? $vendor->created_at->format('d/m/Y') : 'N/A' }}
                            </div>
                            <small class="text-muted">
                                {{ $vendor->created_at ? $vendor->created_at->format('H:i') : '' }}
                            </small>
                        </td>
                        <td>
                            @if ($vendor->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold">
                                    <i class="bi bi-hourglass-split me-1"></i> Chờ duyệt
                                </span>
                            @elseif (in_array($vendor->status, ['active', 'approved']))
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Đã phê duyệt
                                </span>
                            @elseif ($vendor->status === 'rejected')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-bold">
                                    <i class="bi bi-x-circle-fill me-1"></i> Bị từ chối
                                </span>
                            @elseif ($vendor->status === 'suspended')
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1 fw-bold">
                                    <i class="bi bi-slash-circle me-1"></i> Đang tạm khóa
                                </span>
                            @else
                                <span class="badge bg-light text-dark border rounded-pill px-3 py-1">
                                    {{ ucfirst($vendor->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2 align-items-center">
                                @if ($vendor->status === 'pending')
                                    {{-- UC-40: Approve Pending Vendor --}}
                                    <form action="{{ route('admin.vendors.approve', $vendor->vendor_id) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold shadow-sm d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-check-lg"></i>
                                            <span>Phê duyệt</span>
                                        </button>
                                    </form>

                                    {{-- UC-40: Reject Pending Vendor --}}
                                    <form action="{{ route('admin.vendors.reject', $vendor->vendor_id) }}" method="POST" class="m-0" onsubmit="return confirm('Bạn có chắc muốn từ chối đơn đăng ký gian hàng này không?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-x-lg"></i>
                                            <span>Từ chối</span>
                                        </button>
                                    </form>
                                @elseif (in_array($vendor->status, ['active', 'approved']))
                                    {{-- View Storefront Link --}}
                                    <a href="{{ route('vendors.show', $vendor->vendor_id) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2" title="Xem gian hàng">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>

                                    {{-- UC-40: Suspend Active Vendor --}}
                                    <form action="{{ route('admin.vendors.suspend', $vendor->vendor_id) }}" method="POST" class="m-0" onsubmit="return confirm('Bạn có chắc muốn tạm khóa hoạt động gian hàng này không?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1" title="Tạm khóa">
                                            <i class="bi bi-slash-circle"></i>
                                            <span>Tạm khóa</span>
                                        </button>
                                    </form>
                                @elseif ($vendor->status === 'suspended' || $vendor->status === 'rejected')
                                    {{-- UC-40: Reinstate/Re-approve Vendor --}}
                                    <form action="{{ route('admin.vendors.reinstate', $vendor->vendor_id) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span>Mở lại / Duyệt lại</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination Links --}}
    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Hiển thị <strong>{{ $vendors->firstItem() }}</strong> đến <strong>{{ $vendors->lastItem() }}</strong> trong tổng số <strong>{{ $vendors->total() }}</strong> gian hàng
        </div>
        <div>
            {{ $vendors->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
