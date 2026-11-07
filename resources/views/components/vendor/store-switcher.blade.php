@props([
    'vendor',
    'ownedVendors' => collect(),
])

<div class="card border-0 shadow-sm rounded-9 p-3 bg-white mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-10 d-flex align-items-center justify-content-center fg-primary" style="width: 44px; height: 44px;">
                <i class="bi bi-shop fs-lg"></i>
            </div>
            <div>
                <div class="fg-3 small fw-semibold">{{ __('management.active_vendor_store') }}</div>
                <div class="fw-bold fg-body d-flex align-items-center gap-2">
                    <span>{{ $vendor->store_name }}</span>
                    <span class="badge bg-subtle-success fg-success border border-success rounded-pill small">
                        #{{ $vendor->vendor_id }}
                    </span>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            @if ($ownedVendors->count() > 1)
                <form action="{{ route('vendor.store.switch') }}" method="POST" class="d-flex align-items-center gap-2 m-0">
                    @csrf
                    <label for="switch-vendor-select" class="small fg-3 fw-semibold text-nowrap mb-0">{{ __('management.switch_store') }}</label>
                    <select id="switch-vendor-select" name="vendor_id" class="form-control form-control-sm rounded-pill" onchange="this.form.submit()">
                        @foreach ($ownedVendors as $owned)
                            <option value="{{ $owned->vendor_id }}" @selected((int) $owned->vendor_id === (int) $vendor->vendor_id)>
                                {{ $owned->store_name }} (#{{ $owned->vendor_id }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <a href="{{ route('vendor.products.index') }}" class="btn btn-sm {{ request()->routeIs('vendor.products.*') ? 'btn-solid theme-primary' : 'btn-outline theme-primary' }} rounded-pill px-3">
                <i class="bi bi-box-seam me-1"></i>{{ __('management.products_inventory') }}
            </a>
            <a href="{{ route('vendor.store.settings') }}" class="btn btn-sm {{ request()->routeIs('vendor.store.*') ? 'btn-solid theme-primary' : 'btn-outline theme-secondary' }} rounded-pill px-3">
                <i class="bi bi-gear me-1"></i>{{ __('management.store_settings') }}
            </a>
            <a href="{{ route('vendor.categories.index') }}" class="btn btn-sm {{ request()->routeIs('vendor.categories.*') ? 'btn-solid theme-primary' : 'btn-outline theme-secondary' }} rounded-pill px-3">
                <i class="bi bi-tags me-1"></i>{{ __('management.categories') }}
            </a>
            <a href="{{ route('vendors.show', $vendor->vendor_id) }}" target="_blank" class="btn btn-sm btn-solid theme-secondary rounded-pill px-3">
                <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('management.public_storefront') }}
            </a>
        </div>
    </div>
</div>
