<div {{ $attributes->class(['persistent-header position-relative mt-2 px-4 py-2 z-0']) }}>
    <ul class="persistent-header-content d-flex align-items-center gap-3 p-0 overflow-scroll list-unstyled">
        <li>
            <button
                type="button"
                class="nav-button rounded-pill border-0"
                data-locale-panel
                data-bs-toggle="drawer"
                data-bs-target="#settings-panel"
                aria-haspopup="dialog"
                aria-controls="settings-panel"
            >
                <svg class="icon-16" aria-hidden="true">
                    <use href="/images/icons.svg#settings-sliders"></use>
                </svg>
                <span>{{ __('common.settings') }}</span>
            </button>
        </li>
        <li>
            <button type="button" class="nav-button rounded-pill border-0">
                {{ __('common.parcel_delivery') }}
            </button>
        </li>
        <li>
            <button type="button" class="nav-button rounded-pill border-0">
                {{ __('common.phone_sales') }}
            </button>
        </li>
        <li>
            <button type="button" class="nav-button rounded-pill border-0">
                {{ __('common.return_policy') }}
            </button>
        </li>
        {{ $slot }}
    </ul>
</div>

<x-locale-panels />
