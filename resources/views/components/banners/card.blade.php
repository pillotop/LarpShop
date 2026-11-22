@props(['card', 'priority' => false, 'preview' => false, 'sizes' => '100vw'])

@php
    $presentation = in_array($card->presentation, config('banners.presentations'), true)
        ? $card->presentation : 'top_copy';
    $tone = in_array($card->text_tone, config('banners.text_tones'), true) ? $card->text_tone : 'dark';
    $link = $card->link_url;
    $safeLink = \App\Services\Banners\BannerDestination::isSafe($link);
    $interactive = ! $preview && $safeLink;
    $tag = $interactive ? 'a' : 'article';
    $title = $card->localizedText('title');
    $eyebrow = $card->localizedText('eyebrow');
    $badge = $card->localizedText('badge_text');
    $cta = $card->localizedText('cta_text');
    $variants = collect($card->image_variants ?? [])->push([
        'path' => $card->image_path, 'width' => $card->image_width,
    ])->filter(fn ($variant) => ! empty($variant['path']) && ($variant['width'] ?? 0) > 0)
        ->unique('width')->sortBy('width');
    $srcset = $variants->count() > 1 ? $variants->map(fn ($variant) =>
        Storage::disk($card->image_disk)->url($variant['path']).' '.(int) $variant['width'].'w')->implode(', ') : null;
@endphp

<{{ $tag }}
    @if ($interactive)
        href="{{ $link }}"
    @endif
    class="banner-card rounded-2"
    data-presentation="{{ $presentation }}"
    data-text-tone="{{ $tone }}"
    style="--banner-focal-x: {{ max(0, min(100, (int) $card->focal_x)) }}%; --banner-focal-y: {{ max(0, min(100, (int) $card->focal_y)) }}%;"
>
    <img
        src="{{ Storage::disk($card->image_disk)->url($card->image_path) }}"
        @if ($srcset)
            srcset="{{ $srcset }}"
            sizes="{{ $sizes }}"
        @endif
        alt="{{ $card->localizedText('alt_text') }}"
        width="{{ (int) $card->image_width }}"
        height="{{ (int) $card->image_height }}"
        loading="{{ $priority ? 'eager' : 'lazy' }}"
        fetchpriority="{{ $priority ? 'high' : 'auto' }}"
        decoding="async"
        class="banner-card__image"
    >
    @if ($badge)
        <span class="banner-card__badge">{{ $badge }}</span>
    @endif
    <div class="banner-card__copy">
        @if ($eyebrow)
            <p class="banner-card__eyebrow">{{ $eyebrow }}</p>
        @endif
        <h2 class="banner-card__title">{{ $title }}</h2>
        @if ($presentation === 'top_copy' && $cta)
            <span class="banner-card__cta">{{ $cta }}</span>
        @endif
        @if ($presentation === 'bottom_caption')
            <span class="banner-card__action">
                @if ($cta)
                    <span class="banner-card__cta">{{ $cta }}</span>
                @endif
                <span class="banner-card__arrow" aria-hidden="true">&rarr;</span>
            </span>
        @endif
    </div>
</{{ $tag }}>
