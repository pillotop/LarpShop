@once
    <x-side-panel
        :force-drawer="true"
        id="language-panel"
        alignment="bottom"
        :enable-scroll="false"
        aria-labelledby="language-panel-title"
        style="--drawer-backdrop-blur: 0px; --drawer-height: auto;"
        data-locale-panel-surface
    >
        <header class="drawer-header">
            <h2 id="language-panel-title" class="drawer-title">
                {{ __('common.language') }}
            </h2>
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="drawer"
                aria-label="{{ __('common.close') }}"
            ></button>
        </header>
        <div class="drawer-body">
            <div class="container">
                <x-locale-switcher />
            </div>
        </div>
    </x-side-panel>

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
                <details class="accordion-item" open>
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
            </div>
        </div>
    </x-side-panel>
@endonce
