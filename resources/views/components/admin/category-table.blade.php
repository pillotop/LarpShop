@props([
    'categories',
    'parentOptions' => [],
])

@if ($categories->isEmpty())
    <div class="text-center py-5 fg-3">
        <i class="bi bi-inbox fs-3xl d-block mb-2"></i>
        {{ __('management.no_categories_match_your_filters') }}
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-1 text-uppercase small fg-3">
                <tr>
                    <th style="width: 60px;">{{ __('management.id_text') }}</th>
                    <th>{{ __('management.name') }}</th>
                    <th>{{ __('management.slug') }}</th>
                    <th>{{ __('management.parent') }}</th>
                    <th class="text-center">{{ __('management.subcategories') }}</th>
                    <th class="text-center">{{ __('management.products') }}</th>
                    <th class="text-end">{{ __('management.actions') }}</th>
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
                                    <i class="bi bi-pencil"></i> {{ __('management.edit') }}
                                </button>
                                <form action="{{ route('admin.categories.destroy', $category->category_id) }}" method="POST" class="m-0"
                                    data-confirm="{{ __('management.delete_this_category') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline theme-danger rounded-pill px-3">
                                        <i class="bi bi-trash"></i> {{ __('management.delete') }}
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
            {{ __('pagination.summary', ['first' => $categories->firstItem(), 'last' => $categories->lastItem(), 'total' => $categories->total()]) }}
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
                        <h5 class="dialog-title">{{ __('management.display.edit_category') }}{{ $category->category_id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="{{ __('management.close') }}"></button>
                    </div>
                    <div class="dialog-body">
                        <x-admin.category-form-fields :category="$category" :parent-options="$parentOptions" />
                    </div>
                    <div class="dialog-footer">
                        <button type="button" class="btn btn-solid theme-secondary" data-bs-dismiss="dialog">{{ __('management.cancel') }}</button>
                        <button type="submit" class="btn btn-solid theme-primary">{{ __('management.save_changes') }}</button>
                    </div>
                </form>
            </dialog>
    @endforeach
@endif
