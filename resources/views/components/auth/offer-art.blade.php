@props(['slide', 'first' => false])

@php
    [$left, $top, $width, $height] = $slide['bounds'];
@endphp

<div class="auth-offer-media">
    <div
        class="auth-offer-crop"
        style="
            --art-ratio: {{ $width / $height }};
            --art-width: {{ 1024 / $width * 100 }}%;
            --art-left: {{ -$left / $width * 100 }}%;
            --art-top: {{ -$top / $height * 100 }}%;
        "
    >
        <img
            src="{{ asset('images/auth/' . $slide['image']) }}"
            alt="{{ __('auth_showcase.slides.' . $slide['key'] . '.alt') }}"
            width="1024"
            height="1024"
            loading="{{ $first ? 'eager' : 'lazy' }}"
            decoding="async"
            class="auth-offer-art"
        >
    </div>
</div>
