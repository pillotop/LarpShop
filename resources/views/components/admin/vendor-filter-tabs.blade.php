@props(['counts' => [], 'status' => 'all'])

@php
    $filters = [
        'all' => ['label' => 'management.all', 'icon' => null],
        'pending' => ['label' => 'management.pending', 'icon' => 'hourglass-split'],
        'approved' => ['label' => 'management.approved_active', 'icon' => 'check-circle'],
        'rejected' => ['label' => 'management.rejected', 'icon' => 'x-circle'],
        'suspended' => ['label' => 'management.suspended', 'icon' => 'slash-circle'],
    ];
@endphp

<ul class="nav gap-2 mb-4">
    @foreach ($filters as $key => $filter)
        <li class="nav-item">
            <a
                href="{{ route('admin.vendors.index', ['status' => $key]) }}"
                class="nav-link px-3 py-2 fw-semibold fg-body {{ $status === $key ? 'active' : '' }}"
                @if ($status === $key) aria-current="page" @endif
            >
                @if ($filter['icon'])
                    <i class="bi bi-{{ $filter['icon'] }} me-1" aria-hidden="true"></i>
                @endif
                {{ __($filter['label']) }}
                <span class="badge ms-1">{{ $counts[$key] ?? 0 }}</span>
            </a>
        </li>
    @endforeach
</ul>
