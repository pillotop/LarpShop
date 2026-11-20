@extends('layouts.admin')
@section('admin_theme', 'light')

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
            class="btn btn-sm btn-outline theme-secondary rounded-2"
        >{{ __('promotions.collections') }}</a>
    </header>

    @if ($collections->isEmpty())
        <p class="border rounded-2 p-4">{{ __('promotions.no_collections') }}</p>
    @else
        <form
            action="{{ route('admin.promotions.preview') }}"
            method="GET"
            class="row g-3 align-items-end border rounded-2 p-3 mb-4"
        >
            @if ($source)
                <input type="hidden" name="section" value="{{ $source->getKey() }}">
            @endif
            <div class="col-12 md:col-5">
                <label for="preview-collection" class="form-label d-block">{{ __('promotions.collection') }}</label>
                <select id="preview-collection" name="collection" class="form-control rounded-2">
                    @foreach ($collections as $collection)
                        <option
                            value="{{ $collection->getKey() }}"
                            @selected($selectedCollection?->is($collection))
                        >{{ $collection->name }}{{ $collection->is_published ? '' : ' · '.__('promotions.draft') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 md:col-4">
                <label for="preview-layout" class="form-label d-block">{{ __('promotions.layout') }}</label>
                <select id="preview-layout" name="layout" class="form-control rounded-2">
                    @foreach (array_keys(config('promotions.layouts')) as $key)
                        <option value="{{ $key }}" @selected($layout === $key)>
                            {{ __('promotions.layouts.'.$key) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 md:col-3">
                <button type="submit" class="btn btn-solid theme-primary rounded-2 w-100">
                    {{ __('promotions.apply_preview') }}
                </button>
            </div>
        </form>

        <div x-data="{ viewport: 'full' }">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <p class="small fg-3 mb-0">{{ __('promotions.readonly') }}</p>
                <div class="d-flex gap-2" role="group" aria-label="{{ __('promotions.preview') }}">
                    @foreach (['full' => 'desktop', 'narrow' => 'mobile'] as $value => $label)
                        <button
                            type="button"
                            class="btn btn-sm btn-outline theme-secondary rounded-2"
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
                    <p class="border rounded-2 p-4">{{ __('promotions.no_products') }}</p>
                @endif
            </div>
        </div>
    @endif
@endsection
