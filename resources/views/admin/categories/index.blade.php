@extends('layouts.admin')

@section('title', 'Category Management (UC-41)')
@section('page_title', 'Category Management')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
        <header class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
            <div>
                <h1 class="h5 fw-bold fg-body mb-1"><i class="bi bi-diagram-3 fg-primary me-2"></i>Product Categories</h1>
                <p class="fg-3 small mb-0">Create, edit, and organize the category hierarchy used across the storefront.</p>
            </div>
            <button type="button" class="btn btn-solid theme-primary rounded-pill px-4" data-bs-toggle="dialog" data-bs-target="#categoryCreateModal">
                <i class="bi bi-plus-lg me-1"></i> New category
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
                <h5 class="dialog-title">New category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
            </div>
            <div class="dialog-body">
                <x-admin.category-form-fields :category="null" :parent-options="$parentOptions" />
            </div>
            <div class="dialog-footer">
                <button type="button" class="btn btn-solid theme-secondary" data-bs-dismiss="dialog">Cancel</button>
                <button type="submit" class="btn btn-solid theme-primary">Create</button>
            </div>
        </form>
    </dialog>
@endsection
