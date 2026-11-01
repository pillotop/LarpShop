@props([
    'recentVendors',
])

<div class="card border-0 shadow-sm rounded-9 p-4 bg-white h-100">
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <h2 class="h6 fw-bold fg-body mb-0">
            <i class="bi bi-patch-check-fill fg-success me-2"></i>Gian hàng mới kích hoạt
        </h2>
        <a href="{{ route('admin.vendors.index', ['status' => 'approved']) }}" class="small text-decoration-none">Xem tất cả</a>
    </div>

    @if ($recentVendors->isEmpty())
        <p class="fg-3 small py-4 text-center mb-0">Chưa có gian hàng nào đang hoạt động.</p>
    @else
        <div class="list-group list-group-flush">
            @foreach ($recentVendors as $v)
                <div class="list-group-item px-0 py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold fg-body">{{ $v->store_name }}</div>
                            <small class="fg-3">Chủ shop: {{ $v->user->username ?? 'N/A' }}</small>
                        </div>
                        <span class="badge bg-subtle-success fg-success border border-success rounded-pill px-2 py-1">
                            Active
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
