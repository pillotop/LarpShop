@props(['vendor' => null])

@php
    $formId = $vendor?->vendor_id ?? 'new';
    $submitted = (string) old('vendor_form', '') === (string) $formId;
@endphp

<div class="card border-0 shadow-sm rounded-4 p-4">
    <h2 class="h5 fw-bold">{{ $vendor ? 'Resubmit store application' : 'Open a store on LarpShop' }}</h2>
    <p class="text-muted">Create your own storefront to share your LARP products with the community.</p>
    <form action="{{ $vendor ? route('profile.vendor.resubmit', ['vendor' => $vendor->vendor_id]) : route('profile.vendor.register') }}" method="POST">
        @csrf
        <input type="hidden" name="vendor_form" value="{{ $formId }}">
        <div class="mb-3">
            <label for="reg-store-name-{{ $formId }}" class="form-label fw-semibold">Store name <span class="text-danger">*</span></label>
            <input type="text" id="reg-store-name-{{ $formId }}" name="store_name"
                   class="form-control {{ $submitted && $errors->has('store_name') ? 'is-invalid' : '' }}"
                   value="{{ $submitted ? old('store_name', $vendor?->store_name) : $vendor?->store_name }}"
                   required minlength="2" maxlength="100">
            @if ($submitted)
                @error('store_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @endif
        </div>
        <div class="mb-3">
            <label for="reg-description-{{ $formId }}" class="form-label fw-semibold">Store description</label>
            <textarea id="reg-description-{{ $formId }}" name="description" rows="3" maxlength="1000"
                      class="form-control {{ $submitted && $errors->has('description') ? 'is-invalid' : '' }}">{{ $submitted ? old('description', $vendor?->description) : $vendor?->description }}</textarea>
            @if ($submitted)
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @endif
        </div>
        <button type="submit" class="btn btn-primary rounded-pill px-4">Submit application</button>
    </form>
</div>
