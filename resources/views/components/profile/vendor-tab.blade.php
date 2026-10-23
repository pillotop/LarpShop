@props(['vendors'])

@php
    $panes = $vendors->map(fn ($vendor) => [
        'id' => 'tab-vendor-'.$vendor->vendor_id,
        'target_id' => '#pane-vendor-'.$vendor->vendor_id,
        'aria_controls' => 'pane-vendor-'.$vendor->vendor_id,
        'label' => $vendor->store_name,
    ])->all();
    $panes[] = [
        'id' => 'tab-vendor-new',
        'target_id' => '#pane-vendor-new',
        'aria_controls' => 'pane-vendor-new',
        'label' => 'New store',
        'icon' => 'bi-plus-lg',
    ];
    $activeId = $panes[0]['id'];
    $selectedVendor = old('vendor_form', session('vendor_id'));
    if ($selectedVendor !== null) {
        $requestedId = 'tab-vendor-'.$selectedVendor;
        if (in_array($requestedId, array_column($panes, 'id'), true)) {
            $activeId = $requestedId;
        }
    }
@endphp

<div class="tab-pane fade" id="pane-vendor" role="tabpanel" aria-labelledby="tab-vendor-info">
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-2">
                <x-profile.nav-tabs id="vendorTabs" :panes="$panes" :active-id="$activeId" aria-label="Stores" />
            </div>
        </div>
        <div class="col-12 col-md-8">
            <div class="tab-content" id="vendorTabContent">
                @foreach ($vendors as $vendor)
                    <x-profile.vendor-store-pane :vendor="$vendor" :active="$activeId === 'tab-vendor-'.$vendor->vendor_id" />
                @endforeach
                <div class="tab-pane fade {{ $activeId === 'tab-vendor-new' ? 'show active' : '' }}"
                     id="pane-vendor-new" role="tabpanel" aria-labelledby="tab-vendor-new">
                    <x-profile.vendor-registration-form />
                </div>
            </div>
        </div>
    </div>
</div>
