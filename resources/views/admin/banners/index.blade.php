@extends('layouts.admin')
@section('title', __('banner_groups.manage'))
@section('page_title', __('banner_groups.manage'))
@section('admin_page_class', 'banner-preview-page')
@section('head')
    @vite('resources/css/components/banner_groups.css')
@endsection
@section('content')
    <a href="{{ route('admin.dashboard') }}" class="d-inline-block fg-2 mb-3">{{ __('banner_groups.back') }}</a>
    <header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <h1 class="h3">{{ __('banner_groups.manage') }}</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.homepage-layout.index') }}" class="btn btn-outline theme-inverse">{{ __('homepage_layout.title') }}</a>
            <a href="{{ route('admin.banner-groups.preview') }}" class="btn btn-outline theme-inverse">{{ __('banner_groups.preview') }}</a>
            <a href="{{ route('admin.banner-groups.create') }}" class="btn btn-solid theme-inverse">{{ __('banner_groups.create') }}</a>
        </div>
    </header>
    <p class="fg-3">{{ __('banner_groups.management_hint') }}</p>
    <form method="GET" class="d-flex flex-wrap gap-2 mb-4">
        <label for="banner-search" class="visually-hidden">{{ __('banner_groups.search') }}</label>
        <input id="banner-search" type="search" name="q" value="{{ $query }}" maxlength="100" class="form-control w-auto">
        <button class="btn btn-outline theme-inverse">{{ __('banner_groups.search') }}</button>
    </form>
    <div class="table-responsive border">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    @foreach (['name', 'layout', 'status', 'placements', 'actions'] as $field)
                        <th scope="col">{{ __('banner_groups.'.$field) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($groups as $group)
                    <tr>
                        <td>
                            <a href="{{ route('admin.banner-groups.edit', $group) }}">{{ $group->name }}</a>
                            <small class="d-block fg-3">{{ $group->slug }}</small>
                        </td>
                        <td>{{ __('banner_groups.layouts.'.$group->layout) }} · {{ $group->cards_count }}</td>
                        <td>{{ __($group->is_active ? 'banner_groups.enabled' : 'banner_groups.draft') }}</td>
                        <td>
                            @forelse ($group->placements as $assignment)
                                <span class="d-block">{{ __('banner_groups.placement_names.'.$assignment->placement) }} · {{ $assignment->sort_order }}</span>
                            @empty
                                {{ __('banner_groups.unassigned') }}
                            @endforelse
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.banner-groups.edit', $group) }}" class="btn btn-sm btn-outline theme-inverse">{{ __('banner_groups.edit') }}</a>
                                <form method="POST" action="{{ route('admin.banner-groups.destroy', $group) }}" data-confirm="{{ __('banner_groups.delete_confirm') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline theme-inverse">{{ __('banner_groups.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-4">{{ __('banner_groups.no_groups') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $groups->links() }}</div>
@endsection
