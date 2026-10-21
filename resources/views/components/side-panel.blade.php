@props([
    'alignment' => 'start',
    'enableScroll' => true,
    'enableBackdrop' => true,
    'showByDefault' => false,
    'admin' => false,
])

@if ($admin || request()->is('admin*'))
    <x-admin.side-panel />
@elseif ($slot->isNotEmpty())
    <div
        id="side-panel"
        class="offcanvas offcanvas-{{ $alignment }} {{ $showByDefault ? 'show' : '' }} rounded-2"
        tabindex="-1"
        data-bs-scroll="{{ $enableScroll ? 'true' : 'false' }}"
        data-bs-backdrop="{{ $enableBackdrop ? 'true' : 'false' }}"
        {{ $attributes }}
    >
        {{ $slot }}
    </div>
@else
    <div
        id="side-panel"
        class="offcanvas offcanvas-{{ $alignment }} {{ $showByDefault ? 'show' : '' }} rounded-2"
        tabindex="-1"
        data-bs-scroll="{{ $enableScroll ? 'true' : 'false' }}"
        data-bs-backdrop="{{ $enableBackdrop ? 'true' : 'false' }}"
        {{ $attributes }}
    >
    </div>
@endif
