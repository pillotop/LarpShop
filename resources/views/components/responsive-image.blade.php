@props(['image', 'ratio', 'priority' => false, 'sizes' => '100vw'])

@php
    $variants = collect($image->image_variants ?? []);
    $srcset = $variants->isNotEmpty()
        ? $variants->push(['path' => $image->image_path, 'width' => $image->image_width])
            ->sortBy('width')
            ->map(fn ($variant) => Storage::disk($image->image_disk)->url($variant['path']).' '.(int) $variant['width'].'w')
            ->implode(', ')
        : null;
@endphp

<img
    src="{{ Storage::disk($image->image_disk)->url($image->image_path) }}"
    @if ($srcset)
        srcset="{{ $srcset }}"
        sizes="{{ $sizes }}"
    @endif
    alt="{{ $image->alt_text }}"
    width="{{ $image->image_width }}"
    height="{{ $image->image_height }}"
    loading="{{ $priority ? 'eager' : 'lazy' }}"
    fetchpriority="{{ $priority ? 'high' : 'auto' }}"
    decoding="async"
    class="d-block w-100 h-auto object-fit-cover rounded-2"
    style="aspect-ratio: {{ (int) $ratio[0] }} / {{ (int) $ratio[1] }};"
>
