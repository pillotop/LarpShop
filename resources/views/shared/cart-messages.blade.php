@php
    $compareMessages = array_merge(__('products.compare_bar'), ['close' => __('common.close')]);
    $cartMessages = array_merge(__('cart.js'), ['cart_url' => route('cart.index')]);
    $storefrontUxMessages = [
        'suggestions_url' => route('products.suggestions'),
        'search_url' => route('products.index'),
        'suggestions_for' => __('products.search.suggestions_for'),
        'recently_viewed_title' => __('products.recently_viewed.title'),
        'recently_viewed_clear' => __('products.recently_viewed.clear'),
        'wishlist_error' => __('wishlist.error'),
    ];
@endphp
<script type="application/json" id="cart-messages">@json($cartMessages)</script>
<script type="application/json" id="compare-messages">@json($compareMessages)</script>
<script type="application/json" id="storefront-ux-messages">@json($storefrontUxMessages)</script>
