@props([
    'keyword' => '',
    'level' => 'all',
])

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
