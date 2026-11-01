@props([
    'id' => 'side-panel',
    'alignment' => 'start',
    'enableScroll' => true,
    'enableBackdrop' => true,
    'showByDefault' => false,
    'admin' => false,
    'forceDrawer' => false,
])

@if ($admin || (! $forceDrawer && request()->is('admin*')))
    <x-admin.side-panel />
@else
    <dialog
        id="{{ $id }}"
        class="drawer drawer-{{ $alignment }} rounded-4"
        tabindex="-1"
        data-bs-scroll="{{ $enableScroll ? 'true' : 'false' }}"
        data-bs-backdrop="{{ $enableBackdrop ? 'true' : 'false' }}"
        @if ($showByDefault) open @endif
        {{ $attributes }}
    >
        {{ $slot }}
    </dialog>
@endif
