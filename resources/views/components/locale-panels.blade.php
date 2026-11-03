@props([
    'vendor' => null,
])

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
@endonce
