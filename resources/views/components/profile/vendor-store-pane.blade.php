@props(['vendor', 'active' => false])

@php
    $vendorId = $vendor->vendor_id;
    $submitted = (string) old('vendor_form', '') === (string) $vendorId;
    $approved = in_array($vendor->status, ['active', 'approved'], true);
@endphp

<div class="tab-pane fade {{ $active ? 'show active' : '' }}"
     id="pane-vendor-{{ $vendorId }}" role="tabpanel" aria-labelledby="tab-vendor-{{ $vendorId }}">
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div class="stat-card p-3 h-100">
                <div class="text-muted small fw-semibold">Store name</div>
                <div class="fs-5 fw-bold text-dark mt-1 text-break">{{ $vendor->store_name }}</div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="stat-card p-3 h-100">
                <div class="text-muted small fw-semibold">Store status</div>
                <span @class([
                    'badge rounded-pill mt-2',
                    'bg-success-subtle text-success' => $approved,
                    'bg-warning-subtle text-warning-emphasis' => $vendor->status === 'pending',
                    'bg-secondary-subtle text-secondary' => !$approved && $vendor->status !== 'pending',
                ])>{{ ucfirst($vendor->status) }}</span>
            </div>
        </div>
        @if ($vendor->products_count !== null)
            <div class="col-12">
                <div class="stat-card p-3">
                    <div class="text-muted small fw-semibold">Store products</div>
                    <div class="fs-5 fw-bold mt-1">{{ $vendor->products_count }}</div>
                </div>
            </div>
        @endif
    </div>

    @if ($vendor->status === 'pending')
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold">Application awaiting approval</h2>
            <p class="text-muted">Your store application is waiting for administrator review.</p>
            <dl class="mb-0">
                <dt>Submitted</dt>
                <dd>{{ $vendor->created_at?->format('d/m/Y H:i') ?? 'Recently' }}</dd>
                <dt>Store description</dt>
                <dd>{{ $vendor->description ?: 'No description provided.' }}</dd>
            </dl>
        </div>
    @elseif ($vendor->status === 'rejected')
        <div class="alert alert-danger rounded-4">This application was rejected. Update the details below to submit it again.</div>
        <x-profile.vendor-registration-form :vendor="$vendor" />
    @elseif ($approved)
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <h2 class="h5 fw-bold mb-0">Store profile</h2>
                @if ($approved)
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('vendor.store.settings') }}" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="bi bi-gear me-1"></i>Cấu hình gian hàng</a>
                        <a href="{{ route('vendor.categories.index') }}" class="btn btn-outline-info btn-sm rounded-pill"><i class="bi bi-tags me-1"></i>Quản lý danh mục</a>
                        <a href="{{ route('vendors.show', $vendorId) }}" class="btn btn-outline-primary btn-sm rounded-pill" target="_blank">View storefront</a>
                    </div>
                @endif
            </div>
            <form action="{{ route('profile.vendor.update', ['vendor' => $vendorId]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="vendor_form" value="{{ $vendorId }}">
                <div class="mb-3">
                    <label for="store-name-{{ $vendorId }}" class="form-label fw-semibold">Store name <span class="text-danger">*</span></label>
                    <input type="text" id="store-name-{{ $vendorId }}" name="store_name"
                           class="form-control {{ $submitted && $errors->has('store_name') ? 'is-invalid' : '' }}"
                           value="{{ $submitted ? old('store_name', $vendor->store_name) : $vendor->store_name }}"
                           required minlength="2" maxlength="100">
                    @if ($submitted)
                        @error('store_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @endif
                </div>
                <div class="mb-3">
                    <label for="store-description-{{ $vendorId }}" class="form-label fw-semibold">Store description</label>
                    <textarea id="store-description-{{ $vendorId }}" name="description" rows="4" maxlength="1000"
                              class="form-control {{ $submitted && $errors->has('description') ? 'is-invalid' : '' }}">{{ $submitted ? old('description', $vendor->description) : $vendor->description }}</textarea>
                    @if ($submitted)
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @endif
                    <div class="form-text">Up to 1000 characters describing your store.</div>
                </div>
                <button type="submit" class="btn btn-warning rounded-pill px-4">Save store profile</button>
            </form>
        </div>
    @else
        <div class="alert alert-secondary rounded-4">
            This store is currently {{ $vendor->status }}. Contact an administrator for assistance.
        </div>
    @endif
</div>
