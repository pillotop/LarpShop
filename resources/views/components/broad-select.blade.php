@props([
    'groups',
    'heading' => null,
    'introduction' => null,
    'loadStyles' => true,
])

@php
    $instanceId = 'broad-select-'.\Illuminate\Support\Str::uuid();
@endphp

@if ($groups->isNotEmpty())
    @if ($loadStyles)
        @pushOnce('styles', 'broad-select')
            @vite('resources/css/broad_select.css')
        @endPushOnce
    @endif
    <section
        {{ $attributes }}
        data-broad-select
    >
        @if ($heading || $introduction)
            <header class="container">
                <div class="text-center m-3">
                    @if ($heading)
                        <h2>{{ $heading }}</h2>
                    @endif
                    @if ($introduction)
                        <p class="mb-0">{{ $introduction }}</p>
                    @endif
                </div>
            </header>
        @endif
        <div class="broad-select">
            <div class="text-end px-3 mb-2">
                <button
                    type="button"
                    class="btn btn-sm btn-outline theme-secondary rounded-2"
                    data-broad-select-pause
                    aria-pressed="false"
                    aria-label="{{ __('promotions.pause_products') }}"
                >
                    {{ __('promotions.pause_products') }}
                </button>
            </div>
            <div class="selector">
                @include('components.broad-select.choices')
                <div class="broad-select-panels w-100">
                    @foreach ($groups as $group)
                        @include('components.broad-select.products', [
                            'products' => $group->products,
                            'active' => $loop->first,
                            'panelId' => $instanceId.'-panel-'.$loop->index,
                        ])
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
