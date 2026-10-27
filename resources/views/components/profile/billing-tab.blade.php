@props(['addresses'])

@php
    $fields = [[
        'name' => 'recipient_name',
        'label' => 'Recipient name',
        'required' => true,
        'max' => 100,
        'autocomplete' => 'name',
    ], ['name' => 'phone_number', 'label' => 'Phone number', 'max' => 30, 'autocomplete' => 'tel', 'type' => 'tel'], ['name' => 'address_line_1', 'label' => 'Address line 1', 'required' => true, 'max' => 255, 'autocomplete' => 'address-line1'], ['name' => 'address_line_2', 'label' => 'Address line 2', 'max' => 255, 'autocomplete' => 'address-line2'], ['name' => 'city', 'label' => 'City', 'required' => true, 'max' => 100, 'autocomplete' => 'address-level2'], ['name' => 'region', 'label' => 'State / province', 'max' => 100, 'autocomplete' => 'address-level1'], ['name' => 'postal_code', 'label' => 'Postal code', 'max' => 30, 'autocomplete' => 'postal-code'], ['name' => 'country_code', 'label' => 'Country code', 'required' => true, 'max' => 2, 'autocomplete' => 'country']];

    $forms = $addresses->values()->all();
    $forms[] = null;
@endphp

<div class="tab-pane fade" id="pane-billing" role="tabpanel" aria-labelledby="tab-billing">
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h2 class="h5 fw-bold">Billing &amp; addresses</h2>
        <p class="text-muted small">Manage your saved addresses and choose a default for checkout.</p>
        @if ($addresses->isEmpty())
            <p class="text-muted">You have no saved addresses yet.</p>
        @endif
        @foreach ($forms as $address)
            @php
                $formId = (string) ($address?->getKey() ?? 'new');
                $failed = $errors->billing->any() && (string) old('address_form') === $formId;
            @endphp
            <section class="border rounded-3 p-3 mb-3" aria-labelledby="address-heading-{{ $formId }}">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="h6 mb-0" id="address-heading-{{ $formId }}">{{ $address ? $address->recipient_name : 'Add an address' }}</h3>
                    @if ($address?->is_default)
                        <span class="badge text-bg-primary">Default</span>
                    @endif
                </div>
                @if ($address)
                    <address class="small text-muted">
                        {{ $address->address_line_1 }} {{ $address->address_line_2 }}<br>
                        {{ $address->city }}, {{ $address->region }} {{ $address->postal_code }} · {{ $address->country_code }}
                    </address>
                @endif
                <details @if (!$address || $failed) open @endif>
                    <summary class="mb-3">{{ $address ? 'Edit address' : 'Address details' }}</summary>
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
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="address-{{ $formId }}-{{ $name }}">{{ $field['label'] }}{{ !empty($field['required']) ? ' *' : '' }}</label>
                                    <input id="address-{{ $formId }}-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" maxlength="{{ $field['max'] }}" autocomplete="{{ $field['autocomplete'] }}" value="{{ $failed ? old($name) : $address?->getAttribute($name) }}" class="form-control {{ $invalid ? 'is-invalid' : '' }}" @required(!empty($field['required'])) @if ($invalid) aria-invalid="true" aria-describedby="address-error-{{ $formId }}-{{ $name }}" @endif>
                                    @if ($invalid)
                                        <div class="invalid-feedback" id="address-error-{{ $formId }}-{{ $name }}">{{ $errors->billing->first($name) }}</div>
                                    @endif
                                    @if ($name === 'country_code')
                                        <div class="form-text">Use a two-letter code, such as VN or US.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill mt-3">{{ $address ? 'Save changes' : 'Add address' }}</button>
                    </form>
                </details>
                @if ($address)
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @unless ($address->is_default)
                            <form action="{{ route('addresses.default', $address) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-primary btn-sm">Make default</button>
                            </form>
                        @endunless
                        <form action="{{ route('addresses.destroy', $address) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete address</button>
                        </form>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
