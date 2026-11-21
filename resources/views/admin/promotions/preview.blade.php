@extends('layouts.admin')

@section('title', __('promotions.preview'))
@section('page_title', __('promotions.preview'))

@section('head')
    @vite([
        'resources/css/components/promo_sections.css',
        'resources/css/components/product_card.css',
    ])
@endsection

@section('content')
    <header class="mb-4">
        <h1 class="h3 fw-semibold">{{ __('promotions.preview') }}</h1>
        <p class="fg-3">{{ __('promotions.preview_hint') }}</p>
        <a
            href="{{ route('admin.collections.index') }}"
            class="btn btn-sm btn-outline theme-inverse "
        >{{ __('promotions.collections') }}</a>
    </header>

    @include('admin.promotions.partials.preview-controls')

    <div x-data="{ viewport: 'full' }">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <p class="small fg-3 mb-0">{{ __('promotions.readonly') }}</p>
            <div class="d-flex gap-2" role="group" aria-label="{{ __('promotions.preview') }}">
                @foreach (['full' => 'desktop', 'narrow' => 'mobile'] as $value => $label)
                    <button
                        type="button"
                        class="btn btn-sm btn-outline theme-inverse "
                        x-on:click="viewport = '{{ $value }}'"
                        x-bind:aria-pressed="viewport === '{{ $value }}'"
                    >{{ __('promotions.'.$label) }}</button>
                @endforeach
            </div>
        </div>
        <div class="promo-preview-frame" x-bind:data-viewport="viewport">
            @if ($preview && $preview->products->isNotEmpty())
                <x-promo-section :data="$preview" :preview="true" />
            @else
                <p class="border p-4">{{ __($selectedCollection || $productSource !== 'collection' ? 'promotions.no_products' : 'promotions.no_collections') }}</p>
            @endif
        </div>
    </div>
@endsection
