@extends('layouts.admin')
@section('title', __('homepage_layout.title'))
@section('page_title', __('homepage_layout.title'))
@section('admin_page_class', 'banner-preview-page')
@section('head')
    @vite('resources/css/components/banner_groups.css')
@endsection
@section('content')
    <a href="{{ route('admin.dashboard') }}" class="d-inline-block mb-3">{{ __('banner_groups.back') }}</a>
    <h1 class="h3 mb-3">{{ __('homepage_layout.title') }}</h1>
    <p>{{ __('homepage_layout.hint') }}</p>
    <p class="fg-3">{{ __('homepage_layout.visibility') }}</p>
    <p class="fg-3">{{ __('homepage_layout.add_hint') }}</p>
    <p class="small fg-3">{{ __('homepage_layout.legacy_hint') }}</p>
    <div class="d-flex flex-wrap gap-3 mb-4">
        <a href="{{ route('admin.banner-groups.index') }}">{{ __('banner_groups.manage') }}</a>
        <a href="{{ route('admin.sections.index') }}">{{ __('promotions.manage_sections') }}</a>
    </div>
    @if ($blocks->isEmpty())
        <p>{{ __('homepage_layout.empty') }}</p>
    @else
        <form method="POST" action="{{ route('admin.homepage-layout.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="revision" value="{{ $revision }}">
            <div class="table-responsive border rounded-2 mb-4">
                <table class="table align-middle mb-0">
                    <thead><tr>
                        @foreach (['order', 'block', 'state'] as $heading)
                            <th scope="col">{{ __('homepage_layout.'.$heading) }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @foreach ($blocks as $block)
                            @php
                                $position = old('positions.'.$block['key'], $loop->index);
                                $position = is_scalar($position) ? (string) $position : (string) $loop->index;
                            @endphp
                            <tr>
                                <td>
                                    <label class="visually-hidden" for="homepage-order-{{ $loop->index }}">{{ __('homepage_layout.order') }}: {{ $block['label'] }}</label>
                                    <input id="homepage-order-{{ $loop->index }}" type="number" name="positions[{{ $block['key'] }}]" value="{{ $position }}" min="0" max="1000000" required class="form-control" style="min-width: 5rem; max-width: 7rem;">
                                </td>
                                <td>
                                    @if ($block['edit_url'])
                                        <a href="{{ $block['edit_url'] }}">{{ $block['label'] }}</a>
                                    @else
                                        <span>{{ $block['label'] }}</span>
                                    @endif
                                    <small class="d-block fg-3">{{ __('homepage_layout.'.$block['type']) }}</small>
                                </td>
                                <td>{{ __('homepage_layout.'.($block['enabled'] ? 'enabled' : 'disabled')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button class="btn btn-solid theme-inverse rounded-2">{{ __('homepage_layout.save') }}</button>
        </form>
    @endif
@endsection
