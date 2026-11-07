@props([
    'keyword' => '',
    'level' => 'all',
])

<form method="GET" action="{{ route('admin.categories.index') }}" class="row g-2 mb-4">
    <div class="col-12 md:col-6">
        <input type="text" name="q" value="{{ $keyword }}" maxlength="100" class="form-control" placeholder="{{ __('management.search_by_name_or_slug') }}">
    </div>
    <div class="col-6 md:col-3">
        <select name="level" class="form-control">
            <option value="all" @selected($level === 'all')>{{ __('management.all_levels') }}</option>
            <option value="root" @selected($level === 'root')>{{ __('management.top_level_only') }}</option>
            <option value="sub" @selected($level === 'sub')>{{ __('management.subcategories_only') }}</option>
        </select>
    </div>
    <div class="col-6 md:col-3 d-flex gap-2">
        <button type="submit" class="btn btn-solid theme-inverse flex-grow-1">{{ __('management.filter') }}</button>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline theme-secondary">{{ __('management.reset') }}</a>
    </div>
</form>
