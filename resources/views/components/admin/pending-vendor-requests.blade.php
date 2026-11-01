@props([
    'pendingVendorRequests',
    'metrics' => [],
])

<div class="card border-0 shadow-sm rounded-9 p-4 bg-white">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h2 class="h5 fw-bold fg-body mb-1">
                <i class="bi bi-clock-history fg-warning me-2"></i>Đơn đăng ký Vendor chờ duyệt
            </h2>
            <small class="fg-3">Danh sách người dùng đăng ký mở shop cần xét duyệt để kích hoạt quyền bán hàng.</small>
        </div>
        <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline theme-primary rounded-pill px-3">
            Xem tất cả ({{ $metrics['pending_vendors'] ?? $pendingVendorRequests->count() }})
        </a>
    </div>

    @if ($pendingVendorRequests->isEmpty())
        <div class="text-center py-5 fg-3">
            <i class="bi bi-check2-circle fg-success" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mt-3 fg-body">Hiện không có đơn nào đang chờ duyệt!</h5>
            <p class="small fg-3 mb-0">Tất cả đơn đăng ký mở gian hàng đã được xử lý.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-1 small fg-3 text-uppercase">
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
                                <div class="fw-bold fg-body">{{ $request->store_name }}</div>
                                <small class="fg-3 text-truncate d-inline-block" style="max-width: 250px;">
                                    {{ $request->description ?: 'Không có mô tả' }}
                                </small>
                            </td>
                            <td>
                                <div class="fw-semibold fg-body">{{ $request->user->username ?? 'N/A' }}</div>
                                <small class="fg-3">{{ $request->user->email ?? '' }}</small>
                            </td>
                            <td>
                                <span class="small fg-secondary">
                                    {{ $request->created_at ? $request->created_at->format('d/m/Y H:i') : 'N/A' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    {{-- Approve Button --}}
                                    <form action="{{ route('admin.vendors.approve', $request->vendor_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-solid theme-success rounded-pill px-3 fw-semibold shadow-sm" title="Phê duyệt đơn">
                                            <i class="bi bi-check-lg me-1"></i> Duyệt
                                        </button>
                                    </form>

                                    {{-- Reject Button --}}
                                    <form action="{{ route('admin.vendors.reject', $request->vendor_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn từ chối đơn này không?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline theme-danger rounded-pill px-3" title="Từ chối đơn">
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
