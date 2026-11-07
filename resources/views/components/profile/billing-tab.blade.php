@props(['addresses'])

@php
    $fields = [
        [
            'name' => 'recipient_name',
            'label' => __('profile.address.recipient'),
            'max' => 100,
            'autocomplete' => 'name',
            'required' => true,
        ],
        [
            'name' => 'phone_number',
            'label' => __('profile.phone'),
            'max' => 30,
            'autocomplete' => 'tel',
            'type' => 'tel',
        ],
        [
            'name' => 'address_line_1',
            'label' => __('profile.address.line1'),
            'max' => 255,
            'autocomplete' => 'address-line1',
            'required' => true,
        ],
        [
            'name' => 'address_line_2',
            'label' => __('profile.address.line2'),
            'max' => 255,
            'autocomplete' => 'address-line2',
        ],
        [
            'name' => 'city',
            'label' => __('profile.address.city'),
            'max' => 100,
            'autocomplete' => 'address-level2',
            'required' => true,
        ],
        [
            'name' => 'region',
            'label' => __('profile.address.region'),
            'max' => 100,
            'autocomplete' => 'address-level1',
        ],
        [
            'name' => 'postal_code',
            'label' => __('profile.address.postal'),
            'max' => 30,
            'autocomplete' => 'postal-code',
        ],
        [
            'name' => 'country_code',
            'label' => __('profile.address.country'),
            'max' => 2,
            'autocomplete' => 'country',
            'required' => true,
        ],
    ];

    $forms = $addresses->values()->all();
    $forms[] = null;
@endphp

<div class="tab-pane fade" id="pane-billing" role="tabpanel" aria-labelledby="tab-billing">
    <div class="card border-0 shadow-sm rounded-9 p-4">
        <h2 class="h5 fw-bold">{{ __('profile.billing') }}</h2>
        <p class="fg-3 small">{{ __('profile.address.intro') }}</p>
        @if ($addresses->isEmpty())
            <p class="fg-3">{{ __('profile.address.empty') }}</p>
        @endif
        @foreach ($forms as $address)
            @php
                $formId = (string) ($address?->getKey() ?? 'new');
                $failed = $errors->billing->any() && (string) old('address_form') === $formId;
            @endphp
            <section class="border rounded-5 p-3 mb-3" aria-labelledby="address-heading-{{ $formId }}">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="h6 mb-0" id="address-heading-{{ $formId }}">{{ $address ? $address->recipient_name : __('profile.address.add_title') }}</h3>
                    @if ($address?->is_default)
                        <span class="badge theme-primary theme-contrast">{{ __('profile.address.default') }}</span>
                    @endif
                </div>
                @if ($address)
                    <address class="small fg-3">
                        {{ $address->address_line_1 }} {{ $address->address_line_2 }}<br>
                        {{ $address->city }}, {{ $address->region }} {{ $address->postal_code }} · {{ $address->country_code }}
                    </address>
                @endif
                <details @if (!$address || $failed) open @endif>
                    <summary class="mb-3">{{ $address ? __('profile.address.edit') : __('profile.address.details') }}</summary>
                    <form action="{{ $address ? route('addresses.update', $address) : route('addresses.store') }}" method="POST">
                        @csrf
                        @if ($address)
                            @method('PUT')
                        @endif
                        <div class="row g-3">
                            @foreach ($fields as $field)
                                @php
                                    $name = $field['name'];
                                    $invalid = $failed && $errors->billing->has($name);
                                @endphp
                                <div class="col-12 md:col-6">
                                    <label class="form-label" for="address-{{ $formId }}-{{ $name }}">{{ $field['label'] }}{{ !empty($field['required']) ? ' *' : '' }}</label>
                                    <input
                                        id="address-{{ $formId }}-{{ $name }}"
                                        name="{{ $name }}"
                                        type="{{ $field['type'] ?? 'text' }}"
                                        maxlength="{{ $field['max'] }}"
                                        autocomplete="{{ $field['autocomplete'] }}"
                                        value="{{ $failed ? old($name) : $address?->getAttribute($name) }}"
                                        class="form-control {{ $invalid ? 'is-invalid' : '' }}"
                                        @required(!empty($field['required']))
                                        @if ($invalid)
                                            aria-invalid="true"
                                            aria-describedby="address-error-{{ $formId }}-{{ $name }}"
                                        @endif
                                    >
                                    @if ($invalid)
                                        <div class="invalid-feedback" id="address-error-{{ $formId }}-{{ $name }}">{{ $errors->billing->first($name) }}</div>
                                    @endif
                                    @if ($name === 'country_code')
                                        <div class="form-text">{{ __('profile.address.country_help') }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-solid theme-primary rounded-pill mt-3">{{ $address ? __('profile.address.save') : __('profile.address.add') }}</button>
                    </form>
                </details>
                @if ($address)
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @unless ($address->is_default)
                            <form action="{{ route('addresses.default', $address) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline theme-primary btn-sm">{{ __('profile.address.make_default') }}</button>
                            </form>
                        @endunless
                        <form action="{{ route('addresses.destroy', $address) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline theme-danger btn-sm">{{ __('profile.address.delete') }}</button>
                        </form>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
