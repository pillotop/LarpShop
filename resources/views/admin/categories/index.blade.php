@extends('layouts.admin')

@section('title', 'Category Management (UC-41)')
@section('page_title', 'Category Management')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <header class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
            <div>
                <h1 class="h5 fw-bold text-dark mb-1"><i class="bi bi-diagram-3 text-primary me-2"></i>Product Categories</h1>
                <p class="text-muted small mb-0">Create, edit, and organize the category hierarchy used across the storefront.</p>
            </div>
            <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#categoryCreateModal">
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
<div class="modal fade" id="categoryCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="modal-content rounded-4">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">New category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <x-admin.category-form-fields :category="null" :parent-options="$parentOptions" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection
