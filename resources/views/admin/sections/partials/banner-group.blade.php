@php
    $selectedBannerGroup = old('banner_group_id', $section->banner_group_id);
    $selectedBannerGroup = is_scalar($selectedBannerGroup) ? (string) $selectedBannerGroup : '';
@endphp
<div class="my-4">
    <label for="section-banner-group" class="form-label d-block">{{ __('banner_groups.attachment') }}</label>
    <select
        id="section-banner-group"
        name="banner_group_id"
        class="form-control"
        aria-describedby="section-banner-group-hint"
    >
        <option value="">{{ __('banner_groups.no_attachment') }}</option>
        @foreach ($bannerGroups as $group)
            <option
                value="{{ $group->getKey() }}"
                @selected($selectedBannerGroup === (string) $group->getKey())
            >{{ $group->name }} · {{ __('banner_groups.layouts.'.$group->layout) }} · {{ __($group->is_active ? 'banner_groups.enabled' : 'banner_groups.draft') }}</option>
        @endforeach
    </select>
    <p id="section-banner-group-hint" class="small fg-3 mt-2">{{ __('banner_groups.attachment_hint') }}</p>
    <a href="{{ route('admin.banner-groups.index') }}" class="fg-2">{{ __('banner_groups.manage') }}</a>
</div>
