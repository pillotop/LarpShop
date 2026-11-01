@props([
    'category' => null,
    'parentOptions' => [],
])

<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('management.name') }}</label>
    <input type="text" name="name" maxlength="100" required class="form-control" value="{{ $category?->name }}">
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('management.slug') }} <small class="fg-3">{{ __('management.auto_generated_if_empty') }}</small></label>
    <input type="text" name="slug" maxlength="100" class="form-control" value="{{ $category?->slug }}">
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">{{ __('management.parent_category') }}</label>
    <select name="parent_id" class="form-control">
        <option value="">{{ __('management.none_top_level') }}</option>
        @foreach ($parentOptions as $option)
            @continue($category && $option->category_id === $category->category_id)
            <option value="{{ $option->category_id }}" @selected($category && $category->parent_id === $option->category_id)>{{ $option->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-0">
    <label class="form-label fw-semibold">{{ __('management.description') }}</label>
    <textarea name="description" rows="3" maxlength="2000" class="form-control">{{ $category?->description }}</textarea>
</div>
