@props(['user'])

<fieldset class="row g-3 mt-4 pt-3 border-top">
    <legend class="h6 fw-semibold mb-0">{{ __('profile.personal_details') }}</legend>
    <div class="md:col-6">
        <label
            for="date_of_birth"
            class="form-label fw-semibold"
        >
            {{ __('profile.date_of_birth') }}
        </label>
        <input
            id="date_of_birth"
            name="date_of_birth"
            type="date"
            autocomplete="bday"
            max="{{ now()->toDateString() }}"
            value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
            class="form-control rounded-2 @error('date_of_birth') is-invalid @enderror"
            aria-describedby="date-of-birth-help"
            @error('date_of_birth') aria-invalid="true" @enderror
        >
        @error('date_of_birth')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <p
            id="date-of-birth-help"
            class="form-text mb-0"
        >
            {{ __('profile.date_of_birth_help') }}
        </p>
    </div>
    <div class="md:col-6">
        <label
            for="gender"
            class="form-label fw-semibold"
        >
            {{ __('profile.gender.label') }}
        </label>
        <select
            id="gender"
            name="gender"
            class="form-control rounded-2 @error('gender') is-invalid @enderror"
            aria-describedby="gender-help"
            @error('gender') aria-invalid="true" @enderror
        >
            <option value="">{{ __('profile.gender.unspecified') }}</option>
            @foreach (\App\Enums\Gender::cases() as $gender)
                <option
                    value="{{ $gender->value }}"
                    @selected(old('gender', $user->gender?->value) === $gender->value)
                >
                    {{ __('profile.gender.'.$gender->value) }}
                </option>
            @endforeach
        </select>
        @error('gender')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <p
            id="gender-help"
            class="form-text mb-0"
        >
            {{ __('profile.gender.help') }}
        </p>
    </div>
</fieldset>
