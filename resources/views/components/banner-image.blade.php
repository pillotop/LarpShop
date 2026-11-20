@props(['banner', 'ratio', 'priority' => false, 'sizes' => '100vw'])

@php
    $link = $banner->link_url;
    $hasSafeLink = is_string($link)
        && filter_var($link, FILTER_VALIDATE_URL)
        && in_array(strtolower(parse_url($link, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
@endphp

@if ($hasSafeLink)
    <a href="{{ $link }}" class="d-block rounded-2 overflow-hidden">
@endif
    <x-responsive-image
        :image="$banner"
        :ratio="$ratio"
        :priority="$priority"
        :sizes="$sizes"
    />
@if ($hasSafeLink)
    </a>
@endif
