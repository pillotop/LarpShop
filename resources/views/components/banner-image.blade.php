@props(['banner', 'ratio', 'priority' => false, 'sizes' => '100vw'])

@php
    $variants = collect($banner->image_variants ?? []);
    $srcset = $variants->isNotEmpty()
        ? $variants->push(['path' => $banner->image_path, 'width' => $banner->image_width])
            ->sortBy('width')
            ->map(fn ($image) => Storage::disk($banner->image_disk)->url($image['path']).' '.(int) $image['width'].'w')
            ->implode(', ')
        : null;
    $link = $banner->link_url;
    $hasSafeLink = is_string($link)
        && filter_var($link, FILTER_VALIDATE_URL)
        && in_array(strtolower(parse_url($link, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
@endphp

@if ($hasSafeLink)
    <a href="{{ $link }}" class="d-block rounded-2 overflow-hidden">
@endif
    <img
        src="{{ Storage::disk($banner->image_disk)->url($banner->image_path) }}"
        @if ($srcset)
            srcset="{{ $srcset }}"
            sizes="{{ $sizes }}"
        @endif
        alt="{{ $banner->alt_text }}"
        width="{{ $banner->image_width }}"
        height="{{ $banner->image_height }}"
        loading="{{ $priority ? 'eager' : 'lazy' }}"
        fetchpriority="{{ $priority ? 'high' : 'auto' }}"
        decoding="async"
        class="d-block w-100 h-auto object-fit-cover rounded-2"
        style="aspect-ratio: {{ (int) $ratio[0] }} / {{ (int) $ratio[1] }};"
    >
@if ($hasSafeLink)
    </a>
@endif
