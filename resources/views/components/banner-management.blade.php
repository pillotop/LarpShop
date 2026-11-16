@props(['vendor', 'banners' => []])

<div class="d-grid gap-3">
    <p class="small fg-3 mb-0">{{ __('banners.management_help') }}</p>
    @forelse ($banners as $banner)
        <article class="border rounded-2 p-3">
            <img
                src="{{ Storage::disk($banner->image_disk)->url($banner->image_path) }}"
                alt="{{ $banner->alt_text }}"
                width="{{ $banner->image_width }}"
                height="{{ $banner->image_height }}"
                loading="lazy"
                class="w-100 h-auto rounded-2"
            >
            <div class="d-flex justify-content-between gap-2 align-items-start mt-2">
                <h3 class="h6 mb-0">{{ $banner->title }}</h3>
                <span class="small fg-3">{{ __('banners.zones.'.$banner->zone) }}</span>
            </div>
            <p class="small fg-3 my-2">
                {{ $banner->is_active ? __('banners.enabled') : __('banners.disabled') }}
                · {{ __('banners.order') }}: {{ $banner->sort_order }}
            </p>
            <dl class="small mb-2">
                @foreach (['starts_at', 'expires_at'] as $field)
                    <dt>{{ __('banners.'.$field) }}</dt>
                    <dd>{{ $banner->{$field}?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? __('banners.unrestricted') }}</dd>
                @endforeach
            </dl>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <form action="{{ route('vendor.store.banners.toggle', [$vendor, $banner]) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="{{ $banner->is_active ? '0' : '1' }}">
                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-2">
                        {{ $banner->is_active ? __('banners.disable') : __('banners.enable') }}
                    </button>
                </form>
                <form
                    action="{{ route('vendor.store.banners.destroy', [$vendor, $banner]) }}"
                    method="POST"
                    data-confirm="{{ __('banners.confirm_delete') }}"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-link fg-danger rounded-2">{{ __('banners.delete') }}</button>
                </form>
            </div>
            <details @if ((string) old('banner_editor') === (string) $banner->banner_id) open @endif>
                <summary class="small fw-semibold mb-3">{{ __('banners.edit') }}</summary>
                <x-banner-editor :vendor="$vendor" :banner="$banner" />
            </details>
        </article>
    @empty
        <p class="small fg-3">{{ __('banners.no_banners') }}</p>
    @endforelse

    <details class="border rounded-2 p-3" @if (old('banner_editor') === 'new') open @endif>
        <summary class="fw-semibold mb-3">{{ __('banners.add') }}</summary>
        <x-banner-editor :vendor="$vendor" />
    </details>
</div>
