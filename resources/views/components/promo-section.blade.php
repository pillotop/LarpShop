@props(['data', 'viewAllUrl' => null, 'preview' => false, 'loadAssets' => true])

@php
    $definition = config('promotions.layouts')[$data->section->layout] ?? null;
    $component = $definition['component'] ?? null;
    $headingId = 'promo-heading-'.Illuminate\Support\Str::uuid();
    $viewAllUrl = $viewAllUrl ?? (! $preview ? $data->viewAllUrl() : null);
@endphp

@if ($component && $data->products->isNotEmpty())
    @if ($loadAssets)
        @pushOnce('styles', 'promo-sections')
            @vite('resources/css/components/promo_sections.css')
        @endPushOnce
    @endif
    <section
        {{ $attributes->class(['promo-section py-4']) }}
        aria-labelledby="{{ $headingId }}"
        data-promo-layout="{{ $data->section->layout }}"
    >
        <x-promo-layouts.heading
            :data="$data"
            :heading-id="$headingId"
            :view-all-url="$viewAllUrl"
        />
        <x-dynamic-component
            :component="$component"
            :data="$data"
            :preview="$preview"
            :load-assets="$loadAssets"
        />
    </section>
@endif
