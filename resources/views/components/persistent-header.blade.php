@props([
    'vendor' => null,
])

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
        @if ($vendor)
            <li>
                <a href="#storefront-settings">
                    <button type="button" class="nav-button rounded-pill border-0">
                        {{ __('common.storefront_settings') }}
                    </button>
                </a>
            </li>
        @endif
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

@once
    <x-side-panel
        :force-drawer="true"
        id="settings-panel"
        alignment="end"
        :enable-scroll="false"
        aria-labelledby="settings-panel-title"
        data-locale-panel-surface
    >
        <header class="drawer-header">
            <h2 id="settings-panel-title" class="drawer-title">
                {{ __('common.settings') }}
            </h2>
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="drawer"
                aria-label="{{ __('common.close') }}"
            ></button>
        </header>
        <div class="drawer-body">
            <div class="accordion">
                <details class="accordion-item">
                    <summary class="accordion-header">
                        {{ __('common.language') }}
                        <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                    </summary>
                    <div class="accordion-panel">
                        <div class="accordion-body">
                            <x-locale-switcher />
                        </div>
                    </div>
                </details>
                @if ($vendor)
                    <details class="accordion-item">
                        <summary class="accordion-header">
                            {{ __('common.storefront_settings_header') }}
                            <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                        </summary>
                        <div class="accordion-panel">
                            <x-vendor.storefront-settings :vendor="$vendor" />
                        </div>
                    </details>
                @endif
            </div>
        </div>
    </x-side-panel>
@endonce
