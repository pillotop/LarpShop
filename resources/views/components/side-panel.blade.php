@props([
    'alignment' => 'start',
    'enableScroll' => true,
    'enableBackdrop' => true,
    'showByDefault' => false,
])

<div
    id="side-panel"
    class="offcanvas offcanvas-{{ $alignment }} {{ $showByDefault ? 'show' : '' }} rounded-2"
    tabindex="-1"
    data-bs-scroll="{{ $enableScroll ? 'true' : 'false' }}"
    data-bs-backdrop="{{ $enableBackdrop ? 'true' : 'false' }}"
>
</div>
