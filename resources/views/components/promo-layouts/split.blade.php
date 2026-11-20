@props(['data', 'preview' => false])

<div class="promo-split">
    <div class="promo-artwork rounded-2 overflow-hidden">
        @if ($data->section->image_path)
            <x-responsive-image
                :image="$data->section"
                :ratio="config('promotions.image_profiles.portrait.aspect_ratio')"
                sizes="(min-width: 992px) 33vw, 100vw"
            />
        @else
            <div class="promo-image-placeholder d-flex flex-column justify-content-center align-items-center text-center p-4">
                <i class="bi bi-image fs-2xl mb-3" aria-hidden="true"></i>
                <span class="fw-semibold">{{ __('promotions.image_placeholder') }}</span>
                <p class="small fg-3 mt-2 mb-0">{{ __('promotions.image_hint') }}</p>
            </div>
        @endif
    </div>
    <div class="promo-split-products">
        @foreach ($data->products as $product)
            <div @if ($preview) inert @endif>
                <x-product-card-b :product="$product" class="h-100" />
            </div>
        @endforeach
    </div>
</div>
