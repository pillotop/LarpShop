@props(['vendors', 'applications' => null])

@php
    $panes = $vendors->map(fn ($vendor) => [
        'id' => 'tab-vendor-'.$vendor->vendor_id,
        'target_id' => '#pane-vendor-'.$vendor->vendor_id,
        'aria_controls' => 'pane-vendor-'.$vendor->vendor_id,
        'label' => $vendor->store_name,
    ])->all();
    $activeId = $panes[0]['id'] ?? null;
    $selectedVendor = old('vendor_form', session('vendor_id'));
    if ($selectedVendor !== null) {
        $requestedId = 'tab-vendor-'.$selectedVendor;
        if (in_array($requestedId, array_column($panes, 'id'), true)) {
            $activeId = $requestedId;
        }
    }
@endphp

<div class="tab-pane fade" id="pane-vendor" role="tabpanel" aria-labelledby="tab-vendor-info">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <h2 class="h4 fw-bold mb-0">{{ __('profile.stores') }}</h2>
        <a
            href="{{ route('vendor-applications.create') }}"
            class="btn btn-outline theme-primary rounded-2 d-inline-flex align-items-center gap-2"
        >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            {{ __('profile.store.new') }}
        </a>
    </div>

    <x-profile.vendor-applications :applications="$applications" />

    @if ($vendors->isNotEmpty())
        <div class="row g-3">
            <div class="col-12 md:col-4">
                <div class="border rounded-2 p-2">
                    <x-profile.nav-tabs
                        id="vendorTabs"
                        :panes="$panes"
                        :active-id="$activeId"
                        aria-label="{{ __('profile.stores') }}"
                    />
                </div>
            </div>
            <div class="col-12 md:col-8">
                <div class="tab-content" id="vendorTabContent">
                    @foreach ($vendors as $vendor)
                        <x-profile.vendor-store-pane
                            :vendor="$vendor"
                            :active="$activeId === 'tab-vendor-'.$vendor->vendor_id"
                        />
                    @endforeach
                </div>
            </div>
        </div>
    @elseif ($applications === null || $applications->isEmpty())
        <p class="fg-3">{{ __('onboarding.no_stores') }}</p>
    @endif
</div>
