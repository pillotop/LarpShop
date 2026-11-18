@props([
    'vendor' => null,
    'managedBanners' => [],
])

@php
    $canManageStorefront = $vendor !== null
        && Auth::check()
        && $vendor->user_id === Auth::id();
@endphp

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
            <div
                class="accordion"
                x-data
                @if ($canManageStorefront && (session('banner_settings_open') || old('banner_editor')))
                    x-init="$nextTick(() => {
                        $el.querySelector('#storefront-settings').open = true;
                        bootstrap.Drawer.getOrCreateInstance($el.closest('.drawer')).show();
                    })"
                @endif
                x-on:open-settings.window="
                    $el.querySelectorAll('details.accordion-item')
                        .forEach(item => {
                            item.open = item.id === $event.detail.section
                        })
                "
            >

                <details id="language" class="accordion-item" open>
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

                <details id="currency" class="accordion-item">
                    <summary class="accordion-header">
                        {{ __('currency.title') }}
                        <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                    </summary>
                    <div class="accordion-panel">
                        <div class="accordion-body">
                            <x-currency-switcher />
                        </div>
                    </div>
                </details>

                @if ($canManageStorefront)
                <details
                    id="storefront-settings"
                    class="accordion-item"
                >
                    <summary class="accordion-header">
                        {{ __('common.storefront_settings_header') }}
                        <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                    </summary>
                    <div class="accordion-panel">
                        <div class="accordion-body">
                            <x-vendor.storefront-settings :vendor="$vendor" :managed-banners="$managedBanners" />
                        </div>
                    </div>
                </details>
                @endif

            </div>
        </div>
    </x-side-panel>
@endonce
