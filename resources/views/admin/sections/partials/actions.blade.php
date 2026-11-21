<div class="d-flex flex-wrap gap-2">
    <a
        href="{{ route('admin.sections.edit', $section) }}"
        class="btn btn-sm btn-outline theme-inverse "
    >{{ __('promotions.edit') }}</a>
    <a
        href="{{ route('admin.promotions.preview', ['section' => $section->getKey()]) }}"
        class="btn btn-sm btn-outline theme-inverse "
    >{{ __('promotions.preview') }}</a>
    <form action="{{ route('admin.sections.toggle', $section) }}" method="POST">
        @csrf
        @method('PATCH')
        <input type="hidden" name="is_active" value="{{ $section->is_active ? '0' : '1' }}">
        <button type="submit" class="btn btn-sm btn-outline theme-inverse ">{{ __($section->is_active ? 'promotions.disable' : 'promotions.enable') }}</button>
    </form>
    <form
        action="{{ route('admin.sections.destroy', $section) }}"
        method="POST"
        data-confirm="{{ __('promotions.delete_section_confirm') }}"
    >
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline theme-inverse ">{{ __('promotions.delete_section') }}</button>
    </form>
</div>
