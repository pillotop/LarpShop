@props([
    'counts' => [],
    'role' => 'all',
    'keyword' => '',
])

<ul class="nav nav gap-2 mb-3">
    @foreach (['all' => __('management.all'), 'customer' => __('management.customers'), 'admin' => __('management.admins')] as $key => $label)
        <li class="nav-item">
            <a href="{{ route('admin.users.index', array_filter(['role' => $key, 'q' => $keyword])) }}"
                class="nav-link rounded-pill px-3 py-2 fw-semibold {{ $role === $key ? 'active' : 'bg-1 fg-body' }}">
                {{ $label }} <span class="badge bg-secondary rounded-pill ms-1">{{ $counts[$key] ?? 0 }}</span>
            </a>
        </li>
    @endforeach
</ul>

<form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 mb-4">
    <input type="hidden" name="role" value="{{ $role }}">
    <div class="col-12 md:col-9">
        <input type="text" name="q" value="{{ $keyword }}" maxlength="100" class="form-control" placeholder="{{ __('management.search_by_username_email_or_phone') }}">
    </div>
    <div class="col-12 md:col-3 d-flex gap-2">
        <button type="submit" class="btn btn-solid theme-inverse flex-grow-1">{{ __('management.search') }}</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline theme-secondary">{{ __('management.reset') }}</a>
    </div>
</form>
