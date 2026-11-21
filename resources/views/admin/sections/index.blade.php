@extends('layouts.admin')
@section('title', __('promotions.manage_sections'))
@section('page_title', __('promotions.manage_sections'))

@section('content')
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 fw-semibold">{{ __('promotions.manage_sections') }}</h1>
        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route('admin.collections.index') }}"
                class="btn btn-outline theme-inverse "
            >{{ __('promotions.collections') }}</a>
            <a
                href="{{ route('admin.sections.create') }}"
                class="btn btn-solid theme-primary "
            >{{ __('promotions.create_section') }}</a>
        </div>
    </header>
    <p class="fg-3">{{ __('promotions.visibility_hint', ['count' => config('promotions.max_sections')]) }}</p>
    <div class="table-responsive border ">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">{{ __('promotions.heading') }}</th>
                    <th scope="col">{{ __('promotions.layout') }}</th>
                    <th scope="col">{{ __('promotions.schedule') }}</th>
                    <th scope="col">{{ __('promotions.position') }}</th>
                    <th scope="col">{{ __('promotions.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sections as $section)
                    <tr>
                        <td>
                            <a href="{{ route('admin.sections.edit', $section) }}" class="fw-semibold">{{ $section->displayHeading() }}</a>
                            @if ($section->collection)
                                <small class="d-block fg-3">{{ $section->collection->name }} · {{ __($section->collection->is_published ? 'promotions.published' : 'promotions.draft') }}</small>
                            @else
                                <small class="d-block fg-3">{{ __('promotions.sources.'.$section->source) }}</small>
                            @endif
                            <small class="d-block fg-3">{{ __('promotions.placements.'.$section->placement) }}</small>
                        </td>
                        <td>{{ __('promotions.layouts.'.$section->layout) }}</td>
                        <td>
                            <span class="badge">{{ __($section->is_active ? 'promotions.enabled' : 'promotions.disabled') }}</span>
                            <small class="d-block fg-3 mt-1">{{ $section->starts_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }} → {{ $section->expires_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</small>
                            <small class="fg-3">{{ config('app.timezone') }}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                @foreach (['up' => 'arrow-up', 'down' => 'arrow-down'] as $direction => $icon)
                                    <form action="{{ route('admin.sections.move', $section) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="direction" value="{{ $direction }}">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline theme-inverse "
                                            aria-label="{{ __('promotions.move_'.$direction, ['name' => $section->displayHeading()]) }}"
                                        ><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></button>
                                    </form>
                                @endforeach
                            </div>
                        </td>
                        <td>@include('admin.sections.partials.actions')</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-4 fg-3">{{ __('promotions.no_sections') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $sections->links() }}</div>
@endsection
