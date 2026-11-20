@props(['data', 'preview' => false])

<div class="promo-grid">
    @foreach ($data->products as $product)
        <div @if ($preview) inert @endif>
            <x-product-card-b :product="$product" class="h-100" />
        </div>
    @endforeach
</div>
