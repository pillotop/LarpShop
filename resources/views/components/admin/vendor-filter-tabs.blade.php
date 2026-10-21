@props([
    'counts' => [],
    'status' => 'all',
])

<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item">
        <a href="{{ route('admin.vendors.index', ['status' => 'all']) }}" class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $status === 'all' ? 'active' : 'bg-light text-dark' }}">
            Tất cả <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['all'] ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $status === 'pending' ? 'active bg-warning text-dark' : 'bg-light text-dark' }}">
            <i class="bi bi-hourglass-split me-1"></i> Chờ duyệt
            <span class="badge {{ $status === 'pending' ? 'bg-dark text-white' : 'bg-warning text-dark' }} rounded-pill ms-1">{{ $counts['pending'] ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('admin.vendors.index', ['status' => 'approved']) }}" class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $status === 'approved' ? 'active bg-success text-white' : 'bg-light text-dark' }}">
            <i class="bi bi-check-circle-fill me-1"></i> Đã phê duyệt / Hoạt động
            <span class="badge {{ $status === 'approved' ? 'bg-white text-success' : 'bg-success text-white' }} rounded-pill ms-1">{{ $counts['approved'] ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('admin.vendors.index', ['status' => 'rejected']) }}" class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $status === 'rejected' ? 'active bg-danger text-white' : 'bg-light text-dark' }}">
            <i class="bi bi-x-circle-fill me-1"></i> Bị từ chối
            <span class="badge {{ $status === 'rejected' ? 'bg-white text-danger' : 'bg-danger text-white' }} rounded-pill ms-1">{{ $counts['rejected'] ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('admin.vendors.index', ['status' => 'suspended']) }}" class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $status === 'suspended' ? 'active bg-secondary text-white' : 'bg-light text-dark' }}">
            <i class="bi bi-slash-circle me-1"></i> Tạm khóa
            <span class="badge {{ $status === 'suspended' ? 'bg-white text-dark' : 'bg-secondary text-white' }} rounded-pill ms-1">{{ $counts['suspended'] ?? 0 }}</span>
        </a>
    </li>
</ul>
