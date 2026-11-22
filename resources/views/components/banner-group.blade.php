@props(['group', 'priority' => false, 'preview' => false, 'label' => null, 'loadAssets' => true])

@php
    $layout = $group->layout;
    $definition = config('banners.layouts')[$layout] ?? null;
    $cards = $group->cards->where('is_active', true)->sortBy([
        ['sort_order', 'asc'], ['banner_card_id', 'asc'],
    ])->values();
@endphp

@if ($definition && $cards->count() === $definition['card_count'])
    @if ($loadAssets)
        @pushOnce('styles', 'banner-groups')
            @vite('resources/css/components/banner_groups.css')
        @endPushOnce
    @endif
    <section
        {{ $attributes->class(['banner-group']) }}
        aria-label="{{ $label ?? __('banner_groups.region') }}"
        data-banner-layout="{{ $layout }}"
    >
        <div class="banner-group__grid">
            @foreach ($cards as $card)
                <x-banners.card
                    :card="$card"
                    :priority="$priority && $loop->first"
                    :preview="$preview"
                    :sizes="$definition['sizes']"
                />
            @endforeach
        </div>
    </section>
@endif
