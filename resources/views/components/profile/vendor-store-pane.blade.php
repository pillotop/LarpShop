@props(['vendor', 'active' => false])

@php
    $vendorId = $vendor->vendor_id;
    $submitted = (string) old('vendor_form', '') === (string) $vendorId;
    $approved = in_array($vendor->status, ['active', 'approved'], true);
@endphp

<div class="tab-pane fade {{ $active ? 'show active' : '' }}"
     id="pane-vendor-{{ $vendorId }}" role="tabpanel" aria-labelledby="tab-vendor-{{ $vendorId }}">
    <div class="row g-3 mb-4">
        <div class="sm:col-6">
            <div class="stat-card p-3 h-100">
                <div class="fg-3 small fw-semibold">{{ __('profile.store.name') }}</div>
                <div class="fs-lg fw-bold fg-body mt-1 text-break">{{ $vendor->store_name }}</div>
            </div>
        </div>
        <div class="sm:col-6">
            <div class="stat-card p-3 h-100">
                <div class="fg-3 small fw-semibold">{{ __('profile.store.status') }}</div>
                <span @class([
                    'badge rounded-pill mt-2',
                    'bg-subtle-success fg-success' => $approved,
                    'bg-subtle-warning fg-emphasis-warning' => $vendor->status === 'pending',
                    'bg-subtle-secondary fg-secondary' => !$approved && $vendor->status !== 'pending',
                ])>{{ __('products.vendor_status.' . $vendor->status) }}</span>
            </div>
        </div>
        @if ($vendor->products_count !== null)
            <div class="col-12">
                <div class="stat-card p-3">
                    <div class="fg-3 small fw-semibold">{{ __('profile.store.products') }}</div>
                    <div class="fs-lg fw-bold mt-1">{{ $vendor->products_count }}</div>
                </div>
            </div>
        @endif
    </div>

    @if ($vendor->status === 'pending')
        <div class="card border-0 shadow-sm rounded-9 p-4">
            <h2 class="h5 fw-bold">{{ __('profile.store.pending_title') }}</h2>
            <p class="fg-3">{{ __('profile.store.pending_intro') }}</p>
            <dl class="mb-0">
                <dt>{{ __('profile.store.submitted') }}</dt>
                <dd>{{ $vendor->created_at?->format('d/m/Y H:i') ?? __('profile.store.recent') }}</dd>
                <dt>{{ __('profile.store.description') }}</dt>
                <dd>{{ $vendor->description ?: __('profile.store.no_description') }}</dd>
            </dl>
        </div>
    @elseif ($vendor->status === 'rejected')
        <div class="alert theme-danger rounded-9">{{ __('profile.store.rejected') }}</div>
        <x-profile.vendor-registration-form :vendor="$vendor" />
    @elseif ($approved)
        <div class="card border-0 shadow-sm rounded-9 p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <h2 class="h5 fw-bold mb-0">{{ __('profile.store.profile') }}</h2>
                @if ($approved)
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('vendor.products.index', ['vendor_id' => $vendorId]) }}" class="btn btn-outline theme-success btn-sm rounded-pill"><i class="bi bi-box-seam me-1"></i>{{ __('profile.store.inventory') }}</a>
                        <a href="{{ route('vendor.store.settings', ['vendor_id' => $vendorId]) }}" class="btn btn-outline theme-secondary btn-sm rounded-pill"><i class="bi bi-gear me-1"></i>{{ __('profile.store.settings') }}</a>
                        <a href="{{ route('vendor.categories.index', ['vendor_id' => $vendorId]) }}" class="btn btn-outline theme-info btn-sm rounded-pill"><i class="bi bi-tags me-1"></i>{{ __('profile.store.categories') }}</a>
                        <a href="{{ route('vendors.show', $vendorId) }}" class="btn btn-outline theme-primary btn-sm rounded-pill" target="_blank">{{ __('profile.store.view') }}</a>
                    </div>
                @endif
            </div>
            <form action="{{ route('profile.vendor.update', ['vendor' => $vendorId]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="vendor_form" value="{{ $vendorId }}">
                <div class="mb-3">
                    <label for="store-name-{{ $vendorId }}" class="form-label fw-semibold">{{ __('profile.store.name') }} <span class="fg-danger">*</span></label>
                    <input type="text" id="store-name-{{ $vendorId }}" name="store_name"
                           class="form-control {{ $submitted && $errors->has('store_name') ? 'is-invalid' : '' }}"
                           value="{{ $submitted ? old('store_name', $vendor->store_name) : $vendor->store_name }}"
                           required minlength="2" maxlength="100">
                    @if ($submitted)
                        @error('store_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @endif
                </div>
                <div class="mb-3">
                    <label for="store-description-{{ $vendorId }}" class="form-label fw-semibold">{{ __('profile.store.description') }}</label>
                    <textarea id="store-description-{{ $vendorId }}" name="description" rows="4" maxlength="1000"
                              class="form-control {{ $submitted && $errors->has('description') ? 'is-invalid' : '' }}">{{ $submitted ? old('description', $vendor->description) : $vendor->description }}</textarea>
                    @if ($submitted)
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @endif
                    <div class="form-text">{{ __('profile.store.description_help') }}</div>
                </div>
                <button type="submit" class="btn btn-solid theme-warning rounded-pill px-4">{{ __('profile.store.save') }}</button>
            </form>
        </div>
    @else
        <div class="alert theme-secondary rounded-9">
            {{ __('profile.store.unavailable', ['status' => __('products.vendor_status.' . $vendor->status)]) }}
        </div>
    @endif
</div>
