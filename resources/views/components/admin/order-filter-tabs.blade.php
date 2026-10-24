@props([
    'statuses' => [],
    'counts' => [],
    'status' => 'all',
    'keyword' => '',
])

<ul class="nav nav-pills gap-2 mb-3">
    @foreach (array_merge(['all'], $statuses) as $key)
        <li class="nav-item">
            <a href="{{ route('admin.orders.index', array_filter(['status' => $key, 'q' => $keyword])) }}"
                class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $status === $key ? 'active' : 'bg-light text-dark' }}">
                {{ ucfirst($key) }} <span class="badge bg-secondary rounded-pill ms-1">{{ $counts[$key] ?? 0 }}</span>
            </a>
        </li>
    @endforeach
</ul>

<form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 mb-4">
    <input type="hidden" name="status" value="{{ $status }}">
    <div class="col-12 col-md-9">
        <input type="text" name="q" value="{{ $keyword }}" maxlength="100" class="form-control" placeholder="Search by order # or customer username / email">
    </div>
    <div class="col-12 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-dark flex-grow-1">Search</button>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>
