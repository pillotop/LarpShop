<div class="mb-3">
    <label class="form-label fw-semibold">Name</label>
    <input type="text" name="name" maxlength="100" required class="form-control" value="{{ $category?->name }}">
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">Slug <small class="text-muted">(auto-generated if empty)</small></label>
    <input type="text" name="slug" maxlength="100" class="form-control" value="{{ $category?->slug }}">
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">Parent category</label>
    <select name="parent_id" class="form-select">
        <option value="">— None (top-level) —</option>
        @foreach ($parentOptions as $option)
            @continue($category && $option->category_id === $category->category_id)
            <option value="{{ $option->category_id }}" @selected($category && $category->parent_id === $option->category_id)>{{ $option->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-0">
    <label class="form-label fw-semibold">Description</label>
    <textarea name="description" rows="3" maxlength="2000" class="form-control">{{ $category?->description }}</textarea>
</div>
