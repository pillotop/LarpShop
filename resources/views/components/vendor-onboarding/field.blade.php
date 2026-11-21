@props(['name', 'type' => 'text', 'options' => [], 'required' => false, 'max' => null, 'autocomplete' => null])

<div class="onboarding-field">
    <label class="form-label fw-semibold" for="onboarding-{{ $name }}">
        {{ str(__('validation.attributes.'.$name))->ucfirst() }}
        @if ($required)
            <span aria-hidden="true">*</span>
        @else
            <span class="fw-normal fg-3">({{ __('onboarding.optional') }})</span>
        @endif
    </label>
    @if ($type === 'select')
        <select
            id="onboarding-{{ $name }}"
            name="{{ $name }}"
            x-model="form.{{ $name }}"
            class="form-control rounded-2"
            :class="{ 'is-invalid': fieldError('{{ $name }}') }"
            :aria-invalid="Boolean(fieldError('{{ $name }}'))"
            aria-describedby="onboarding-{{ $name }}-error"
            @if ($required) aria-required="true" @endif
        >
            <option value="">{{ __('onboarding.choose') }}</option>
            @foreach ($options as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea
            id="onboarding-{{ $name }}"
            name="{{ $name }}"
            x-model="form.{{ $name }}"
            class="form-control rounded-2"
            rows="4"
            maxlength="{{ $max }}"
            :class="{ 'is-invalid': fieldError('{{ $name }}') }"
            :aria-invalid="Boolean(fieldError('{{ $name }}'))"
            aria-describedby="onboarding-{{ $name }}-error"
        ></textarea>
    @else
        <input
            id="onboarding-{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            x-model="form.{{ $name }}"
            class="form-control rounded-2"
            :class="{ 'is-invalid': fieldError('{{ $name }}') }"
            :aria-invalid="Boolean(fieldError('{{ $name }}'))"
            aria-describedby="onboarding-{{ $name }}-error"
            @if ($max) maxlength="{{ $max }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($required) aria-required="true" @endif
        >
    @endif
    <p
        id="onboarding-{{ $name }}-error"
        class="small text-danger mt-1 mb-0"
        x-show="fieldError('{{ $name }}')"
        x-text="fieldError('{{ $name }}')"
        x-cloak
    ></p>
</div>
