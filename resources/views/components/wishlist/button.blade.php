@props([
    'product',
    'compact' => false,
])

@php
    $saved = in_array($product->getKey(), $wishlistProductIds);
    $label = $saved ? __('wishlist.remove') : __('wishlist.save');
@endphp

@auth
    <form
        action="{{ route($saved ? 'wishlist.destroy' : 'wishlist.store', $product) }}"
        method="POST"
        class="d-inline-flex"
    >
        @csrf
        @if ($saved)
            @method('DELETE')
        @endif

        <button
            type="submit"
            {{ $attributes->merge(['class' => 'btn btn-sm btn-outline theme-secondary']) }}
            aria-label="{{ $label }}: {{ $product->name }}"
            title="{{ $label }}"
            aria-pressed="{{ $saved ? 'true' : 'false' }}"
        >
            <i
                class="bi {{ $saved ? 'bi-heart-fill fg-danger' : 'bi-heart' }}"
                aria-hidden="true"
            ></i>
            <span @class(['visually-hidden' => $compact])>{{ $label }}</span>
        </button>
    </form>
@else
    <a
        href="{{ route('login') }}"
        data-bs-toggle="drawer"
        data-bs-target="#auth-panel"
        {{ $attributes->merge(['class' => 'btn btn-sm btn-outline theme-secondary']) }}
        aria-label="{{ __('wishlist.sign_in', ['name' => $product->name]) }}"
    >
        <i class="bi bi-heart" aria-hidden="true"></i>
        <span @class(['visually-hidden' => $compact])>{{ __('wishlist.save') }}</span>
    </a>
@endauth
