@once
    <x-side-panel
        :force-drawer="true"
        id="auth-panel"
        alignment="end"
        :enable-scroll="false"
        aria-labelledby="login-form-header"
        style="--drawer-width: min(440px, 100vw);"
        data-locale-panel-surface
    >
        <header class="drawer-header border-bottom-0 pb-0 d-flex justify-content-end">
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="drawer"
                aria-label="{{ __('common.close') }}"
            ></button>
        </header>
        <div class="drawer-body pt-2 px-4 pb-4">
            <x-auth.login-form />
        </div>
    </x-side-panel>
@endonce
