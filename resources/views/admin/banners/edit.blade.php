@extends('layouts.admin')
@section('title', __($group->exists ? 'banner_groups.edit' : 'banner_groups.create'))
@section('page_title', __('banner_groups.manage'))
@section('admin_page_class', 'banner-preview-page')
@section('head')
    @vite('resources/css/components/banner_groups.css')
@endsection
@section('content')
    @php
        $rows = app(\App\Data\Banners\BannerEditorData::class)->rows($group);
        $textValue = fn ($value) => \App\Data\Banners\BannerEditorData::text($value);
        $assignments = old('placements', $group->placements->pluck('placement')->all());
        $assignments = is_array($assignments) ? $assignments : [];
        $placeholder = new \App\Models\BannerCard([
            'title' => __('banner_groups.fields.title'), 'eyebrow' => ' ', 'badge_text' => ' ', 'cta_text' => ' ',
            'alt_text' => '', 'image_disk' => 'public_assets', 'image_path' => 'product-placeholder.svg',
            'image_width' => 800, 'image_height' => 1000, 'presentation' => 'bottom_caption', 'text_tone' => 'dark',
            'focal_x' => 50, 'focal_y' => 50,
        ]);
    @endphp
    <header class="mb-4">
        <a href="{{ route('admin.banner-groups.index') }}" class="fg-2">{{ __('banner_groups.manage') }}</a>
        <h1 class="h3 mt-3">{{ __($group->exists ? 'banner_groups.edit' : 'banner_groups.create') }}</h1>
        <p class="fg-3">{{ __('banner_groups.editor_hint') }}</p>
    </header>
    <noscript><p class="border p-3">{{ __('banner_groups.javascript_required') }}</p></noscript>
    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ $group->exists ? route('admin.banner-groups.update', $group) : route('admin.banner-groups.store') }}"
        data-banner-editor
        data-counts="{{ json_encode(array_map(fn ($definition) => $definition['card_count'], config('banners.layouts'))) }}"
        data-count-message="{{ __('banner_groups.count_hint') }}"
        data-image-error="{{ __('banner_groups.invalid_image') }}"
        data-max-bytes="{{ config('banners.cards_upload.max_bytes') }}"
        data-max-pixels="{{ config('banners.cards_upload.max_pixels') }}"
        data-placeholder="{{ asset('images/product-placeholder.svg') }}"
        data-title-placeholder="{{ __('banner_groups.fields.title') }}"
    >
        @csrf
        @if ($group->exists) @method('PUT') @endif
        <div class="row g-3 border p-3 mb-4">
            @foreach (['name', 'slug'] as $field)
                <div class="col-12 md:col-6">
                    <label for="banner-group-{{ $field }}" class="form-label d-block">{{ __('banner_groups.'.$field) }}</label>
                    <input id="banner-group-{{ $field }}" name="{{ $field }}" value="{{ $textValue(old($field, $group->{$field})) }}" maxlength="255" class="form-control" required>
                </div>
            @endforeach
            <div class="col-12 md:col-6">
                <label for="banner-group-layout" class="form-label d-block">{{ __('banner_groups.layout') }}</label>
                <select id="banner-group-layout" name="layout" class="form-control" data-editor-layout>
                    @foreach (array_keys(config('banners.layouts')) as $layout)
                        <option value="{{ $layout }}" @selected(old('layout', $group->layout) === $layout)>{{ __('banner_groups.layouts.'.$layout) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 md:col-6 d-flex align-items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input id="banner-group-active" name="is_active" value="1" type="checkbox" class="form-check-input" @checked(old('is_active', $group->is_active))>
                <label for="banner-group-active">{{ __('banner_groups.publish') }}</label>
            </div>
            @foreach (['starts_at', 'expires_at'] as $field)
                <div class="col-12 md:col-6">
                    <label for="banner-group-{{ $field }}" class="form-label d-block">{{ __('banners.'.$field) }}</label>
                    <input id="banner-group-{{ $field }}" name="{{ $field }}" type="datetime-local" value="{{ $textValue(old($field, $group->{$field}?->format('Y-m-d\TH:i'))) }}" class="form-control">
                </div>
            @endforeach
            <p class="small fg-3 mb-0">{{ __('banners.timezone', ['timezone' => config('app.timezone')]) }}</p>
            <fieldset class="col-12">
                <legend class="h6">{{ __('banner_groups.placements') }}</legend>
                @foreach (config('banners.placements') as $placement)
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <input id="banner-placement-{{ $placement }}" name="placements[]" value="{{ $placement }}" type="checkbox" class="form-check-input" @checked(in_array($placement, $assignments, true))>
                        <label for="banner-placement-{{ $placement }}">{{ __('banner_groups.placement_names.'.$placement) }}</label>
                        <label for="banner-order-{{ $placement }}">{{ __('banner_groups.priority') }}</label>
                        <input id="banner-order-{{ $placement }}" name="placement_orders[{{ $placement }}]" type="number" min="0" max="1000000" class="form-control w-auto" value="{{ $textValue(old('placement_orders.'.$placement, $group->placements->firstWhere('placement', $placement)?->sort_order ?? 0)) }}">
                    </div>
                @endforeach
                <p class="small fg-3 mt-2">{{ __('banner_groups.placement_hint') }}</p>
            </fieldset>
        </div>
        <details id="banner-editor-preview" class="border p-3 mb-4" open>
            <summary class="fw-semibold">{{ __('banner_groups.live_preview') }}</summary>
            <div class="d-flex flex-wrap align-items-center gap-3 my-3">
                <label for="banner-editor-locale">{{ __('banner_groups.preview_language') }}</label>
                <select id="banner-editor-locale" class="form-control w-auto" data-editor-locale>
                    <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                    <option value="vi" @selected(app()->getLocale() === 'vi')>Tiếng Việt</option>
                </select>
                <label for="banner-editor-width">{{ __('banner_groups.width') }}</label>
                <select id="banner-editor-width" class="form-control w-auto" data-editor-width>
                    @foreach (['full', 'tablet', 'mobile'] as $width)
                        <option value="{{ $width }}">{{ __('banner_groups.widths.'.$width) }}</option>
                    @endforeach
                </select>
            </div>
            <p class="small fg-3">{{ __('banner_groups.unsaved_preview') }}</p>
            <div class="banner-preview-frame" data-editor-frame>
                <section class="banner-group" aria-label="{{ __('banner_groups.region') }}" data-editor-preview>
                    <div class="banner-group__grid" data-preview-grid></div>
                </section>
            </div>
        </details>
        <p class="fw-semibold" data-editor-count role="status" aria-live="polite"></p>
        <div data-editor-cards>
            @foreach ($rows as $index => $values)
                @include('admin.banners.partials.card-fields', compact('index', 'values'))
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-3 my-4">
            <button type="button" class="btn btn-outline theme-inverse" data-editor-add>{{ __('banner_groups.add_card') }}</button>
            <button type="submit" class="btn btn-solid theme-inverse">{{ __('banner_groups.save') }}</button>
        </div>
        <p class="small fg-3">{{ __('banner_groups.upload_retry') }}</p>
        <template data-editor-card-template>
            @include('admin.banners.partials.card-fields', ['index' => '__INDEX__', 'values' => []])
        </template>
        <template data-editor-tile-template>
            <x-banners.card :card="$placeholder" :preview="true" />
        </template>
    </form>
@endsection
@section('tail')
    @vite('resources/js/components/banner_group_editor.js')
@endsection
