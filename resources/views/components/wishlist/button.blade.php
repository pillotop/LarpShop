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
        data-wishlist-form
        data-product-id="{{ $product->getKey() }}"
        data-store-url="{{ route('wishlist.store', $product) }}"
        data-destroy-url="{{ route('wishlist.destroy', $product) }}"
        data-saved="{{ $saved ? '1' : '0' }}"
        data-save-aria="{{ __('wishlist.save') }}: {{ $product->name }}"
        data-remove-aria="{{ __('wishlist.remove') }}: {{ $product->name }}"
        data-save-text="{{ __('wishlist.save') }}"
        data-remove-text="{{ __('wishlist.remove') }}"
        data-compact="{{ $compact ? '1' : '0' }}"
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
            <span @class(['visually-hidden' => $compact]) data-wishlist-label>{{ $label }}</span>
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
