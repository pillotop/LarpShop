@extends('layouts.admin')
@section('title', __('promotions.manage_sections'))
@section('page_title', __('promotions.manage_sections'))

@section('content')
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 fw-semibold">{{ __($section->exists ? 'promotions.edit_section' : 'promotions.create_section') }}</h1>
        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route('admin.sections.index') }}"
                class="btn btn-outline theme-inverse "
            >{{ __('promotions.back_sections') }}</a>
            @if ($section->exists)
                <a
                    href="{{ route('admin.promotions.preview', ['section' => $section->getKey()]) }}"
                    class="btn btn-outline theme-inverse "
                >{{ __('promotions.preview') }}</a>
            @endif
        </div>
    </header>
    @include('admin.sections.partials.form')
@endsection
