@extends('layouts.admin')

@section('title', __('promotions.collections'))
@section('page_title', __('promotions.collections'))

@section('content')
    <header class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 fw-semibold">{{ __('promotions.collections') }}</h1>
            <p class="fg-3 mb-0">{{ __('promotions.collections_hint') }}</p>
        </div>
        <a href="{{ route('admin.collections.create') }}" class="btn btn-solid theme-primary ">
            {{ __('promotions.create_collection') }}
        </a>
    </header>

    <form action="{{ route('admin.collections.index') }}" method="GET" class="d-flex gap-2 mb-4">
        <label for="collection-search" class="visually-hidden">{{ __('promotions.search_collections') }}</label>
        <input
            id="collection-search"
            type="search"
            name="q"
            value="{{ $search }}"
            maxlength="100"
            class="form-control "
            placeholder="{{ __('promotions.search_collections') }}"
        >
        <button type="submit" class="btn btn-outline theme-inverse ">{{ __('promotions.search') }}</button>
    </form>

    <div class="table-responsive border mb-4">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">{{ __('promotions.name') }}</th>
                    <th scope="col">{{ __('promotions.status') }}</th>
                    <th scope="col">{{ __('promotions.products') }}</th>
                    <th scope="col">{{ __('promotions.sections') }}</th>
                    <th scope="col">{{ __('promotions.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($collections as $collection)
                    <tr>
                        <th scope="row">
                            <a href="{{ route('admin.collections.edit', $collection) }}">{{ $collection->name }}</a>
                            <small class="d-block fg-3 fw-normal">{{ $collection->slug }}</small>
                        </th>
                        <td>{{ $collection->is_published ? __('promotions.published') : __('promotions.draft') }}</td>
                        <td>{{ $collection->products_count }}</td>
                        <td>{{ $collection->sections_count }}</td>
                        <td>
                            <a href="{{ route('admin.collections.edit', $collection) }}">{{ __('promotions.edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="fg-3 py-4">{{ __('promotions.no_collections') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $collections->links() }}
@endsection
