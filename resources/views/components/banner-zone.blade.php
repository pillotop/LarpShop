@props([
    'zone',
    'banners' => [],
    'priority' => false,
    'sizes' => '100vw',
])

@php
    $items = collect($banners)->values();
    $definition = config('banners.zones')[$zone] ?? null;
    $id = 'banner-zone-'.Illuminate\Support\Str::uuid();
@endphp

@if ($definition && $items->isNotEmpty())
    <section
        {{ $attributes->class(['rounded-2', 'carousel' => $items->count() > 1]) }}
        id="{{ $id }}"
        aria-label="{{ __('banners.zones.'.$zone) }}"
        data-banner-zone="{{ $zone }}"
    >
        @if ($items->count() === 1)
            <x-banner-image :banner="$items->first()" :ratio="$definition['aspect_ratio']" :priority="$priority" :sizes="$sizes" />
        @else
            <div class="carousel-inner rounded-2">
                @foreach ($items as $banner)
                    <div
                        class="carousel-item"
                        role="group"
                        aria-label="{{ __('banners.slide', ['current' => $loop->iteration, 'total' => $loop->count]) }}"
                    >
                        <x-banner-image
                            :banner="$banner"
                            :ratio="$definition['aspect_ratio']"
                            :priority="$priority && $loop->first"
                            :sizes="$sizes"
                        />
                    </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-end gap-2 mt-2">
                @foreach (['prev' => 'previous', 'next' => 'next'] as $direction => $label)
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary rounded-2"
                        data-bs-target="#{{ $id }}"
                        data-bs-slide="{{ $direction }}"
                    >
                        {{ __('banners.'.$label) }}
                    </button>
                @endforeach
            </div>
        @endif
    </section>
@endif
