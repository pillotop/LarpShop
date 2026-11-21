@extends('layouts.admin')

@section('title', $collection->name)
@section('page_title', __('promotions.edit_collection'))

@section('content')
    <header class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <h1 class="h3 fw-semibold">{{ $collection->name }}</h1>
        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route('admin.sections.create', ['collection' => $collection->getKey()]) }}"
                class="btn btn-solid theme-primary "
            >{{ __('promotions.create_section') }}</a>
            <a
                href="{{ route('admin.promotions.preview', ['collection' => $collection->getKey()]) }}"
                class="btn btn-outline theme-inverse "
            >{{ __('promotions.preview') }}</a>
            @if ($collection->is_published)
                <a
                    href="{{ route('collections.show', ['slug' => $collection->slug]) }}"
                    class="btn btn-outline theme-inverse "
                >{{ __('promotions.view_collection') }}</a>
            @endif
        </div>
    </header>
    @include('admin.collections.partials.form')
    @include('admin.collections.partials.members')
    @include('admin.collections.partials.product-picker')

    <section class="border-top mt-5 pt-4">
        <h2 class="h5">{{ __('promotions.delete_collection') }}</h2>
        <p class="small fg-3">{{ __('promotions.delete_hint') }}</p>
        <form
            action="{{ route('admin.collections.destroy', $collection) }}"
            method="POST"
            data-confirm="{{ __('promotions.delete_confirm', ['name' => $collection->name]) }}"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline theme-inverse ">{{ __('promotions.delete_collection') }}</button>
        </form>
    </section>
@endsection
