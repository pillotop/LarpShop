@props([
    'vendor',
    'ownedVendors' => collect(),
])

<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 44px; height: 44px;">
                <i class="bi bi-shop fs-5"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold">Active Vendor Store</div>
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <span>{{ $vendor->store_name }}</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">
                        #{{ $vendor->vendor_id }}
                    </span>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            @if ($ownedVendors->count() > 1)
                <form action="{{ route('vendor.store.switch') }}" method="POST" class="d-flex align-items-center gap-2 m-0">
                    @csrf
                    <label for="switch-vendor-select" class="small text-muted fw-semibold text-nowrap mb-0">Switch store:</label>
                    <select id="switch-vendor-select" name="vendor_id" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                        @foreach ($ownedVendors as $owned)
                            <option value="{{ $owned->vendor_id }}" @selected((int) $owned->vendor_id === (int) $vendor->vendor_id)>
                                {{ $owned->store_name }} (#{{ $owned->vendor_id }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <a href="{{ route('vendor.products.index') }}" class="btn btn-sm {{ request()->routeIs('vendor.products.*') ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-3">
                <i class="bi bi-box-seam me-1"></i>Products & Inventory
            </a>
            <a href="{{ route('vendor.store.settings') }}" class="btn btn-sm {{ request()->routeIs('vendor.store.*') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3">
                <i class="bi bi-gear me-1"></i>Store Settings
            </a>
            <a href="{{ route('vendor.categories.index') }}" class="btn btn-sm {{ request()->routeIs('vendor.categories.*') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3">
                <i class="bi bi-tags me-1"></i>Categories
            </a>
            <a href="{{ route('vendors.show', $vendor->vendor_id) }}" target="_blank" class="btn btn-sm btn-light rounded-pill px-3">
                <i class="bi bi-box-arrow-up-right me-1"></i>Public Storefront
            </a>
        </div>
    </div>
</div>
