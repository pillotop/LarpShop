@extends('layouts.admin')

@section('title', __('banner_groups.preview'))
@section('page_title', __('banner_groups.preview'))
@section('admin_page_class', 'banner-preview-page')

@section('head')
    @vite('resources/css/components/banner_groups.css')
@endsection

@section('content')
    <header class="mb-4">
        <a href="{{ route('admin.dashboard') }}" class="d-inline-block fg-2 mb-3">
            {{ __('banner_groups.back') }}
        </a>
        <h1 class="h3 fw-semibold">{{ __('banner_groups.preview') }}</h1>
        <p class="fg-3">{{ __('banner_groups.preview_hint') }}</p>
    </header>

    <form
        method="GET"
        action="{{ route('admin.banner-groups.preview') }}"
        class="d-flex flex-wrap align-items-end gap-3 mb-4"
    >
        @foreach ([
            'layout' => array_keys(config('banners.layouts')),
            'presentation' => config('banners.presentations'),
            'tone' => config('banners.text_tones'),
            'width' => ['full', 'tablet', 'mobile'],
        ] as $field => $options)
            <div>
                <label for="banner-preview-{{ $field }}" class="form-label d-block">
                    {{ __('banner_groups.'.$field) }}
                </label>
                <select
                    id="banner-preview-{{ $field }}"
                    name="{{ $field }}"
                    class="form-control rounded-2"
                >
                    @foreach ($options as $option)
                        <option value="{{ $option }}" @selected($$field === $option)>
                            {{ __('banner_groups.'.match ($field) {
                                'layout' => 'layouts', 'presentation' => 'presentations',
                                'tone' => 'tones', 'width' => 'widths',
                            }.'.'.$option) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <button type="submit" class="btn btn-solid theme-inverse rounded-2">
            {{ __('banner_groups.apply') }}
        </button>
    </form>

    <p class="small fg-3">{{ __('banner_groups.readonly') }}</p>
    <div class="banner-preview-frame" data-width="{{ $width }}">
        <x-banner-group :group="$group" :preview="true" :load-assets="false" />
    </div>
@endsection
