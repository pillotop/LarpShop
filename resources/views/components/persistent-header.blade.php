@props([
    'vendor' => null,
])

<div {{ $attributes->class(['persistent-header position-relative mt-2 px-4 py-2 z-0']) }}>
    <ul class="persistent-header-content d-flex align-items-center gap-3 p-0 overflow-scroll list-unstyled">
        <li class="flex-shrink-0">
            <button
                type="button"
                class="nav-button rounded-pill border-0 px-3"
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

        @if ($vendor?->user_id === Auth::id())
        <li class="flex-shrink-0">
            <button
                x-data
                x-on:click="$dispatch('open-settings', { section: 'storefront-settings' })"
                data-bs-toggle="drawer"
                data-bs-target="#settings-panel"
                type="button"
                class="nav-button rounded-pill border-0 px-3"
            >
                {{ __('common.storefront_settings') }}
            </button>
        </li>
        @endif

        <li class="flex-shrink-0">
            <button type="button" class="nav-button rounded-pill border-0 px-3">
                {{ __('common.parcel_delivery') }}
            </button>
        </li>
        <li class="flex-shrink-0">
            <button type="button" class="nav-button rounded-pill border-0 px-3">
                {{ __('common.phone_sales') }}
            </button>
        </li>
        <li class="flex-shrink-0">
            <button type="button" class="nav-button rounded-pill border-0 px-3">
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
            <div
                class="accordion"
                x-data
                x-on:open-settings.window="
                    $el.querySelectorAll('details.accordion-item')
                        .forEach(item => {
                            item.open = item.id === $event.detail.section
                        })
                "
            >

                <details id="language" class="accordion-item">
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

                @if ($vendor?->user_id === Auth::id())
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
                            <x-vendor.storefront-settings :vendor="$vendor" />
                        </div>
                    </div>
                </details>
                @endif

            </div>
        </div>
    </x-side-panel>
@endonce
