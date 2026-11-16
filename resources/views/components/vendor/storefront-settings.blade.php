@props([
    'vendor',
])

<div class="divide-y">
    @if (session('success'))
        <p class="fg-success" role="status">{{ session('success') }}</p>
    @endif

    @if ($errors->any())
        <div class="fg-danger" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    <form
        action="{{ route('vendor.store.name.update', $vendor) }}"
        method="POST"
        class="my-2"
    >
        @csrf
        @method('PATCH')
        <label
            for="store-name-{{ $vendor->vendor_id }}"
            class="h4"
        >{{ __('profile.store.name') }}</label>
        <input
            id="store-name-{{ $vendor->vendor_id }}"
            type="text"
            name="store_name"
            value="{{ old('store_name', $vendor->store_name) }}"
            class="form-control mt-2"
            minlength="2"
            maxlength="100"
            required
        >
        <button
            type="submit"
            class="btn btn-primary mt-2"
        >
            {{ __('profile.store.update_name') }}
        </button>
    </form>

    <form
        action="{{ route('vendor.store.profile.update', $vendor) }}"
        method="POST"
        enctype="multipart/form-data"
        class="my-2"
    >
        @csrf
        @method('PATCH')
        <header>
            <span class="h4">{{ __('profile.store.profile') }}</span>
        </header>

        @if ($vendor->brand_path)
            <div
                class="border border-dashed rounded-1"
                style="width: 76px; height: 76px;"
            >
                <img
                    class="profile-image w-100 h-100 object-fit-cover"
                    src="{{ $vendor->profile_image_url }}"
                    alt="{{ __('profile.profile_container.alt_text') }}"
                />
            </div>
        @else
            <p class="my-2">{{ __('profile.store.profile_not_present') }}</p>
        @endif

        <x-file-dropzone
            name="brand_image"
            accept="image/jpeg,image/png,image/webp"
            class="rounded-1 border border-dashed border-secondary border-1"
        >
            <div data-file-placeholder>
                <i class="bi bi-cloud-arrow-up fs-2"></i>
                <div>{{ __('profile.store.change_profile') }}</div>
            </div>
        </x-file-dropzone>

        <button
            type="submit"
            class="btn btn-primary mt-2"
        >
            {{ __('profile.store.update_image') }}
        </button>
    </form>

    <form
        action="{{ route('vendor.store.description.update', $vendor) }}"
        method="POST"
        class="my-2"
    >
        @csrf
        @method('PATCH')
        <header>
            <span class="h4">{{ __('profile.store.description') }}</span>
        </header>

        <label
            for="store-description-{{ $vendor->vendor_id }}"
            class="visually-hidden"
        >{{ __('profile.store.description') }}</label>
        <textarea
            id="store-description-{{ $vendor->vendor_id }}"
            name="description"
            class="form-control mt-2"
            rows="5"
            maxlength="2000"
        >{{ old('description', $vendor->description) }}</textarea>

        <button
            type="submit"
            class="btn btn-primary mt-2"
        >
            {{ __('profile.store.update_description') }}
        </button>
    </form>

    <section class="my-2">
        <header>
            <span class="h4">{{ __('profile.store.banner') }}</span>
        </header>

        <p class="small fg-3">{{ __('banners.demo') }}</p>
        <x-image-cropper
            :ratio="config('banners.zones.vendor_header.aspect_ratio')"
            :width="config('banners.zones.vendor_header.output_width')"
            :max-bytes="config('banners.upload.max_source_bytes')"
            :max-pixels="config('banners.upload.max_source_pixels')"
        />
    </section>
</div>
