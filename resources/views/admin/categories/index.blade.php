@extends('layouts.admin')

@section('title', __('management.category_management').' (UC-41)')
@section('page_title', __('management.category_management'))

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
        <header class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
            <div>
                <h1 class="h5 fw-bold fg-body mb-1"><i class="bi bi-diagram-3 fg-primary me-2"></i>{{ __('management.product_categories') }}</h1>
                <p class="fg-3 small mb-0">{{ __('management.categories_intro') }}</p>
            </div>
            <button type="button" class="btn btn-solid theme-primary rounded-pill px-4" data-bs-toggle="dialog" data-bs-target="#categoryCreateModal">
                <i class="bi bi-plus-lg me-1"></i> {{ __('management.new_category') }}
            </button>
        </header>

        {{-- Category Filters Component (UC-41) --}}
        <x-admin.category-filter :keyword="$keyword" :level="$level" />

        {{-- Category Table & Modals Component (UC-41) --}}
        <x-admin.category-table :categories="$categories" :parent-options="$parentOptions" />
    </div>
</div>

{{-- Create modal --}}
<dialog class="dialog" id="categoryCreateModal" tabindex="-1">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="d-flex flex-column rounded-9">
            @csrf
            <div class="dialog-header">
                <h5 class="dialog-title">{{ __('management.new_category') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="{{ __('management.close') }}"></button>
            </div>
            <div class="dialog-body">
                <x-admin.category-form-fields :category="null" :parent-options="$parentOptions" />
            </div>
            <div class="dialog-footer">
                <button type="button" class="btn btn-solid theme-secondary" data-bs-dismiss="dialog">{{ __('management.cancel') }}</button>
                <button type="submit" class="btn btn-solid theme-primary">{{ __('management.create') }}</button>
            </div>
        </form>
    </dialog>
@endsection
