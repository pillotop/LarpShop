@props(['data', 'viewAllUrl' => null, 'preview' => false])

@php
    $definition = config('promotions.layouts')[$data->section->layout] ?? null;
    $component = $definition['component'] ?? null;
    $headingId = 'promo-heading-'.Illuminate\Support\Str::uuid();
    $viewAllUrl = $viewAllUrl ?? (! $preview && $data->section->collection->is_published
        ? route('collections.show', ['slug' => $data->section->collection->slug])
        : null);
@endphp

@if ($component && $data->products->isNotEmpty())
    @once
        @vite('resources/css/components/promo_sections.css')
    @endonce
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
        />
    </section>
@endif
