@props([
    'panes',
    'id',
    'activeId' => null,
    'orientation' => 'vertical',
])

<ul
    {{ $attributes->class(['nav profile-nav-tabs gap-2', 'flex-column' => $orientation === 'vertical']) }}
    id="{{ $id }}"
    aria-orientation="{{ $orientation }}"
    role="tablist"
>
    @foreach ($panes as $pane)
        @php($active = $activeId !== null ? $pane['id'] === $activeId : $loop->first)
        <li class="nav-item" role="presentation">
            <button
                class="nav-link w-100 text-start d-flex align-items-center gap-2 {{ $active ? 'active' : '' }}"
                id="{{ $pane['id'] }}"
                data-bs-toggle="tab"
                data-bs-target="{{ $pane['target_id'] }}"
                type="button"
                role="tab"
                aria-controls="{{ $pane['aria_controls'] }}"
                aria-selected="{{ $active ? 'true' : 'false' }}"
            >
                @if (!empty($pane['icon']))
                    <i class="bi {{ $pane['icon'] }} fs-lg"></i>
                @endif
                <span>{{ $pane['label'] }}</span>
                @if (!empty($pane['badge']))
                    <span class="badge bg-warning fg-body ms-auto">{{ $pane['badge'] }}</span>
                @endif
            </button>
        </li>
    @endforeach
</ul>
