@props(['user'])

<div class="d-flex flex-wrap align-items-center gap-3 mb-4 pb-4 border-bottom">
    <x-profile.profile-container :account="$user" class="flex-shrink-0" />
    <div class="flex-grow-1">
        <label
            for="profile_image"
            class="form-label fw-semibold"
        >
            {{ __('profile.photo.label') }}
        </label>
        <input
            id="profile_image"
            name="profile_image"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="form-control rounded-2 @error('profile_image') is-invalid @enderror"
            aria-describedby="profile-image-help"
            @error('profile_image') aria-invalid="true" @enderror
        >
        @error('profile_image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <p
            id="profile-image-help"
            class="form-text mb-0"
        >
            {{ __('profile.photo.help') }}
        </p>
        @if ($user->profile_path)
            <div class="mt-2">
                <input
                    id="remove_profile_image"
                    name="remove_profile_image"
                    type="checkbox"
                    value="1"
                    class="checkbox"
                    @checked(old('remove_profile_image'))
                >
                <label for="remove_profile_image">{{ __('profile.photo.remove') }}</label>
            </div>
        @endif
        @error('remove_profile_image')
            <p class="text-danger small mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
