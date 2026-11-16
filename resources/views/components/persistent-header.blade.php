<div {{ $attributes->class(['persistent-header position-relative mt-2 px-3 sm:px-4 py-2 z-0']) }}>
    <ul class="persistent-header-content d-flex flex-nowrap align-items-center gap-2 sm:gap-3 p-0 m-0 overflow-x-auto list-unstyled">
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
            <a href="{{ route('help') }}#orders" class="nav-button rounded-pill border-0 text-decoration-none">
                <i class="bi bi-truck" aria-hidden="true"></i>
                <span>{{ __('common.parcel_delivery') }}</span>
            </a>
        </li>
        <li>
            <a href="{{ route('contact') }}" class="nav-button rounded-pill border-0 text-decoration-none">
                <i class="bi bi-telephone" aria-hidden="true"></i>
                <span>{{ __('common.phone_sales') }}</span>
            </a>
        </li>
        <li>
            <a href="{{ route('help') }}#returns" class="nav-button rounded-pill border-0 text-decoration-none">
                <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
                <span>{{ __('common.return_policy') }}</span>
            </a>
        </li>
        {{ $slot }}
    </ul>
</div>

<x-locale-panels />
