@props([
    'categories',
    'parentOptions' => [],
])

@if ($categories->isEmpty())
    <div class="text-center py-5 fg-3">
        <i class="bi bi-inbox fs-3xl d-block mb-2"></i>
        No categories match your filters.
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-1 text-uppercase small fg-3">
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
                        <td class="fg-3 fw-bold">#{{ $category->category_id }}</td>
                        <td>
                            <div class="fw-semibold fg-body">{{ $category->name }}</div>
                            @if ($category->description)
                                <small class="fg-secondary d-block text-truncate" style="max-width: 320px;">{{ $category->description }}</small>
                            @endif
                        </td>
                        <td><code>{{ $category->slug }}</code></td>
                        <td>{{ $category->parent?->name ?? '—' }}</td>
                        <td class="text-center">{{ $category->children_count }}</td>
                        <td class="text-center">{{ $category->products_count }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline theme-primary rounded-pill px-3"
                                    data-bs-toggle="dialog" data-bs-target="#categoryEditModal{{ $category->category_id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form action="{{ route('admin.categories.destroy', $category->category_id) }}" method="POST" class="m-0"
                                    onsubmit="return confirm('Delete this category?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline theme-danger rounded-pill px-3">
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
        <div class="small fg-3">
            Showing <strong>{{ $categories->firstItem() }}</strong> to <strong>{{ $categories->lastItem() }}</strong> of <strong>{{ $categories->total() }}</strong> categories
        </div>
        <div>{{ $categories->links('shared.pagination') }}</div>
    </div>

    {{-- Edit modals --}}
    @foreach ($categories as $category)
        <dialog class="dialog" id="categoryEditModal{{ $category->category_id }}" tabindex="-1">
                <form method="POST" action="{{ route('admin.categories.update', $category->category_id) }}" class="d-flex flex-column rounded-9">
                    @csrf
                    @method('PUT')
                    <div class="dialog-header">
                        <h5 class="dialog-title">Edit category #{{ $category->category_id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
                    </div>
                    <div class="dialog-body">
                        <x-admin.category-form-fields :category="$category" :parent-options="$parentOptions" />
                    </div>
                    <div class="dialog-footer">
                        <button type="button" class="btn btn-solid theme-secondary" data-bs-dismiss="dialog">Cancel</button>
                        <button type="submit" class="btn btn-solid theme-primary">Save changes</button>
                    </div>
                </form>
            </dialog>
    @endforeach
@endif
