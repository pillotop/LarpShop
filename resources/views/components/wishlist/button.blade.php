@props([
    'product',
    'compact' => false,
])

@php
    $saved = in_array($product->getKey(), $wishlistProductIds);
    $label = $saved ? 'Remove from wishlist' : 'Save to wishlist';
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
            class="btn btn-sm btn-outline-secondary"
            aria-label="{{ $label }}: {{ $product->name }}"
            title="{{ $label }}"
            aria-pressed="{{ $saved ? 'true' : 'false' }}"
        >
            <i
                class="bi {{ $saved ? 'bi-heart-fill text-danger' : 'bi-heart' }}"
                aria-hidden="true"
            ></i>
            <span @class(['visually-hidden' => $compact])>{{ $label }}</span>
        </button>
    </form>
@else
    <a
        href="{{ route('login') }}"
        class="btn btn-sm btn-outline-secondary"
        aria-label="Sign in to save {{ $product->name }} to your wishlist"
    >
        <i class="bi bi-heart" aria-hidden="true"></i>
        <span @class(['visually-hidden' => $compact])>Save to wishlist</span>
    </a>
@endauth
