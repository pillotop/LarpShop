@props([
    'variant' => 'spinner',
    'label' => null,
    'hidden' => false,
])

<div
    {{ $attributes->class(['loading-state']) }}
    data-loading-state
    role="status"
    aria-live="polite"
    aria-atomic="true"
    @if ($hidden) hidden @endif
>
    @if ($variant === 'skeleton')
        <span class="visually-hidden">{{ $label ?? __('common.loading') }}</span>
        <div class="placeholder-glow" aria-hidden="true">
            @if ($slot->isNotEmpty())
                {{ $slot }}
            @else
                <span class="placeholder rounded-2 col-8 mb-2"></span>
                <span class="placeholder rounded-2 col-12 mb-2"></span>
                <span class="placeholder rounded-2 col-6"></span>
            @endif
        </div>
    @else
        <div class="d-flex align-items-center gap-2">
            <span class="spinner-border" aria-hidden="true"></span>
            <span>{{ $label ?? __('common.loading') }}</span>
        </div>
    @endif
</div>
