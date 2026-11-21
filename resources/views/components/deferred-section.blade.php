@props(['source', 'label', 'errorMessage'])

<div
    {{ $attributes->class(['deferred-section']) }}
    data-deferred-section
    data-source="{{ $source }}"
>
    <x-loading-state variant="skeleton" :label="$label">
        {{ $slot }}
    </x-loading-state>
    <div data-deferred-content></div>
    <div
        data-deferred-error
        class="container py-4 text-center"
        hidden
    >
        <p role="status">{{ $errorMessage }}</p>
        <button
            type="button"
            class="btn btn-outline theme-secondary rounded-2"
            data-deferred-retry
        >
            {{ __('common.retry') }}
        </button>
    </div>
</div>
<noscript>
    <style>[data-deferred-section] { display: none; }</style>
    <p class="container text-center">
        <a href="{{ route('products.index') }}">{{ __('products.home.browse') }}</a>
    </p>
</noscript>
