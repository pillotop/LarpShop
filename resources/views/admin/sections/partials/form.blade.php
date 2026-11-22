<form
    action="{{ $section->exists ? route('admin.sections.update', $section) : route('admin.sections.store') }}"
    method="POST"
    enctype="multipart/form-data"
    class="border p-3 lg:p-4"
    x-data="promoSourceForm(@js([
        'layout' => old('layout', $section->layout),
        'source' => old('source', $section->source),
        'placement' => old('placement', $section->placement),
        'broadPlacement' => config('promotions.broad_select.placement'),
        'profiles' => array_map(fn ($definition) => $definition['image_profile'], config('promotions.layouts')),
    ]))"
>
    @csrf
    @if ($section->exists)
        @method('PUT')
    @endif
    @include('admin.sections.partials.fields')
    @include('admin.sections.partials.banner-group')
    @include('admin.sections.partials.schedule')
    @include('admin.sections.partials.image')
    <button type="submit" class="btn btn-solid theme-primary mt-4">{{ __('promotions.save_section') }}</button>
</form>
