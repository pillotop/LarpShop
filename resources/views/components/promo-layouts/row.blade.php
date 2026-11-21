@props(['data', 'preview' => false, 'loadAssets' => true])

@if ($loadAssets)
    @pushOnce('styles', 'two-arrow-scroller')
        @vite('resources/css/components/two_arrow_scroller.css')
    @endPushOnce
    @pushOnce('scripts', 'two-arrow-scroller-script')
        @vite('resources/js/components/two_arrow_scroller.js')
    @endPushOnce
@endif

@php($contentId = 'promo-products-'.Illuminate\Support\Str::uuid())

<x-two-arrow-scroller :content-id="$contentId">
    <ul
        class="promo-product-row d-flex gap-3 list-unstyled overflow-x-auto m-0 pb-3"
        id="{{ $contentId }}"
        data-scroll-content
        tabindex="0"
        aria-label="{{ $data->heading() }}"
    >
        @foreach ($data->products as $product)
            <li class="promo-row-item" @if ($preview) inert @endif>
                <x-product-card-b :product="$product" class="h-100" />
            </li>
        @endforeach
    </ul>
</x-two-arrow-scroller>
