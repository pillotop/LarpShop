@php($compareMessages = array_merge(__('products.compare_bar'), ['close' => __('common.close')]))
<script type="application/json" id="cart-messages">@json(__('cart.js'))</script>
<script type="application/json" id="compare-messages">@json($compareMessages)</script>
