<form
    action="{{ $section->exists ? route('admin.sections.update', $section) : route('admin.sections.store') }}"
    method="POST"
    enctype="multipart/form-data"
    class="border rounded-2 p-3 lg:p-4"
    x-data="{ layout: @js(old('layout', $section->layout)), profiles: @js(array_map(fn ($definition) => $definition['image_profile'], config('promotions.layouts'))) }"
>
    @csrf
    @if ($section->exists)
        @method('PUT')
    @endif
    @include('admin.sections.partials.fields')
    @include('admin.sections.partials.schedule')
    @include('admin.sections.partials.image')
    <button type="submit" class="btn btn-solid theme-primary rounded-2 mt-4">{{ __('promotions.save_section') }}</button>
</form>
