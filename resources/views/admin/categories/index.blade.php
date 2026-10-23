@extends('layouts.admin')

@section('title', 'Category Management (UC-41)')
@section('page_title', 'Category Management')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
            <div>
                <h1 class="h5 fw-bold text-dark mb-1"><i class="bi bi-diagram-3 text-primary me-2"></i>Product Categories</h1>
                <p class="text-muted small mb-0">Create, edit, and organize the category hierarchy used across the storefront.</p>
            </div>
            <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#categoryCreateModal">
                <i class="bi bi-plus-lg me-1"></i> New category
            </button>
        </div>

        <form method="GET" action="{{ route('admin.categories.index') }}" class="row g-2 mb-4">
            <div class="col-12 col-md-6">
                <input type="text" name="q" value="{{ $keyword }}" maxlength="100" class="form-control" placeholder="Search by name or slug">
            </div>
            <div class="col-6 col-md-3">
                <select name="level" class="form-select">
                    <option value="all" @selected($level === 'all')>All levels</option>
                    <option value="root" @selected($level === 'root')>Top-level only</option>
                    <option value="sub" @selected($level === 'sub')>Subcategories only</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1">Filter</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        @if ($categories->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No categories match your filters.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Parent</th>
                            <th class="text-center">Subcategories</th>
                            <th class="text-center">Products</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="text-muted fw-bold">#{{ $category->category_id }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $category->name }}</div>
                                    @if ($category->description)
                                        <small class="text-secondary d-block text-truncate" style="max-width: 320px;">{{ $category->description }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $category->slug }}</code></td>
                                <td>{{ $category->parent?->name ?? '—' }}</td>
                                <td class="text-center">{{ $category->children_count }}</td>
                                <td class="text-center">{{ $category->products_count }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                            data-bs-toggle="modal" data-bs-target="#categoryEditModal{{ $category->category_id }}">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form action="{{ route('admin.categories.destroy', $category->category_id) }}" method="POST" class="m-0"
                                            onsubmit="return confirm('Delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing <strong>{{ $categories->firstItem() }}</strong> to <strong>{{ $categories->lastItem() }}</strong> of <strong>{{ $categories->total() }}</strong> categories
                </div>
                <div>{{ $categories->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif
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
                @include('admin.categories._fields', ['category' => null, 'parentOptions' => $parentOptions])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit modals --}}
@foreach ($categories as $category)
    <div class="modal fade" id="categoryEditModal{{ $category->category_id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.categories.update', $category->category_id) }}" class="modal-content rounded-4">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit category #{{ $category->category_id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.categories._fields', ['category' => $category, 'parentOptions' => $parentOptions])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
