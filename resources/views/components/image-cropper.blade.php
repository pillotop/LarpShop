@props([
    'name' => 'banner_image',
    'ratio' => [4, 1],
    'width' => 1600,
    'maxBytes' => 12582912,
    'maxPixels' => 24000000,
])

@php
    $height = (int) round($width * $ratio[1] / $ratio[0]);
    $settings = [
        'width' => $width,
        'height' => $height,
        'maxBytes' => $maxBytes,
        'maxPixels' => $maxPixels,
        'messages' => collect(['editing', 'ready', 'invalid_source', 'invalid_pixels', 'decode_error', 'apply_first'])
            ->mapWithKeys(fn ($key) => [$key => __('banners.'.$key)])->all(),
    ];
@endphp

@pushOnce('styles', 'image-cropper')
    @vite('resources/css/components/image_cropper.css')
@endPushOnce
@pushOnce('scripts', 'image-cropper-script')
    @vite('resources/js/components/image_cropper.js')
@endPushOnce

<image-cropper
    {{ $attributes->class(['image-cropper', 'd-block']) }}
    data-settings="{{ json_encode($settings) }}"
>
    <label class="image-cropper-picker border border-dashed rounded-2 p-3 d-block">
        <span class="fw-semibold">{{ __('banners.choose') }}</span>
        <span class="d-block small fg-3 mt-1">
            {{ __('banners.hint', ['size' => round($maxBytes / 1048576), 'ratio' => implode(':', $ratio)]) }}
        </span>
        <input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="form-control mt-2"
            data-crop-source
        >
    </label>

    {{-- Only the explicitly confirmed crop is submitted. --}}
    <input
        type="file"
        name="{{ $name }}"
        hidden
        data-crop-output
    >

    <div data-crop-editor hidden>
        <p class="small fg-3 mt-3">{{ __('banners.instructions') }}</p>
        <canvas
            width="{{ $width }}"
            height="{{ $height }}"
            class="image-cropper-canvas rounded-2"
            role="img"
            aria-label="{{ __('banners.preview') }}"
        ></canvas>

        <div class="image-cropper-controls mt-3">
            @foreach (['zoom' => [1, 3, 1], 'horizontal' => [0, 1, 0.5], 'vertical' => [0, 1, 0.5]] as $control => $limits)
                <label class="small">
                    <span>{{ __('banners.'.$control) }}</span>
                    <input
                        type="range"
                        min="{{ $limits[0] }}"
                        max="{{ $limits[1] }}"
                        value="{{ $limits[2] }}"
                        step="0.01"
                        class="form-range d-block w-100"
                        data-crop-control="{{ $control }}"
                    >
                </label>
            @endforeach
        </div>

        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="button" class="btn btn-primary rounded-2" data-crop-apply>
                {{ __('banners.apply') }}
            </button>
            <button type="button" class="btn btn-outline-secondary rounded-2" data-crop-reset>
                {{ __('banners.reset') }}
            </button>
            <button type="button" class="btn btn-link rounded-2" data-crop-clear>
                {{ __('banners.clear') }}
            </button>
        </div>
    </div>

    <p class="small fg-3 mt-2 mb-0" role="status" aria-live="polite" data-crop-status>
        {{ __('banners.empty') }}
    </p>
    <noscript>{{ __('banners.javascript_required') }}</noscript>
</image-cropper>
