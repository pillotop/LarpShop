@props(['vendor', 'banner' => null])

@php
    $editor = $banner ? (string) $banner->banner_id : 'new';
    $restoring = (string) old('banner_editor') === $editor;
    $value = fn ($field, $fallback = null) => $restoring ? old($field, $fallback) : ($banner?->{$field} ?? $fallback);
    $zones = array_filter(config('banners.zones'), fn ($zone) => $zone['context'] === 'vendor');
    $zone = $value('zone', 'vendor_header');
    $definition = $zones[$zone] ?? $zones['vendor_header'];
@endphp

<form
    action="{{ $banner ? route('vendor.store.banners.update', [$vendor, $banner]) : route('vendor.store.banners.store', $vendor) }}"
    method="POST"
    enctype="multipart/form-data"
    class="d-grid gap-3"
>
    @csrf
    @if ($banner)
        @method('PATCH')
    @endif
    <input type="hidden" name="banner_editor" value="{{ $editor }}">

    @foreach (['title', 'alt_text'] as $field)
        <label class="d-block small">
            <span>{{ __('banners.'.$field) }}</span>
            <input
                type="text"
                name="{{ $field }}"
                value="{{ $value($field) }}"
                maxlength="255"
                required
                class="form-control rounded-2 mt-1"
            >
        </label>
    @endforeach

    <label class="d-block small">
        <span>{{ __('banners.zone') }}</span>
        <select name="zone" class="form-select rounded-2 mt-1">
            @foreach ($zones as $key => $settings)
                <option value="{{ $key }}" @selected($zone === $key)>{{ __('banners.zones.'.$key) }}</option>
            @endforeach
        </select>
    </label>

    <label class="d-block small">
        <span>{{ __('banners.link') }}</span>
        <input
            type="url"
            name="link_url"
            value="{{ $value('link_url') }}"
            maxlength="2048"
            class="form-control rounded-2 mt-1"
        >
    </label>

    <p class="small fg-3 mb-0">{{ __('banners.timezone', ['timezone' => config('app.timezone')]) }}</p>
    @foreach (['starts_at', 'expires_at'] as $field)
        <label class="d-block small">
            <span>{{ __('banners.'.$field) }}</span>
            <input
                type="datetime-local"
                name="{{ $field }}"
                value="{{ $restoring ? old($field) : $banner?->{$field}?->timezone(config('app.timezone'))->format('Y-m-d\TH:i') }}"
                class="form-control rounded-2 mt-1"
            >
        </label>
    @endforeach

    <label class="d-block small">
        <span>{{ __('banners.order') }}</span>
        <input
            type="number"
            name="sort_order"
            value="{{ $value('sort_order', 0) }}"
            min="0"
            max="1000000"
            required
            class="form-control rounded-2 mt-1"
        >
        <span class="fg-3">{{ __('banners.order_help') }}</span>
    </label>

    <input type="hidden" name="is_active" value="0">
    <label class="d-flex gap-2 align-items-center small">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            @checked($value('is_active', false))
        >
        <span>{{ __('banners.active') }}</span>
    </label>

    @if ($banner)
        <p class="small fg-3 mb-0">{{ __('banners.keep_image') }}</p>
    @endif
    <x-image-cropper
        :ratio="$definition['aspect_ratio']"
        :width="$definition['output_width']"
        :max-bytes="config('banners.upload.max_source_bytes')"
        :max-pixels="config('banners.upload.max_source_pixels')"
    />
    <button type="submit" class="btn btn-primary rounded-2">{{ __('banners.save') }}</button>
</form>
