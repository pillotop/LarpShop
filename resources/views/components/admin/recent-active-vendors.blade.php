@props([
    'recentVendors',
])

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
