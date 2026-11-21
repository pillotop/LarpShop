@props(['user', 'addresses'])

<section
    id="pane-account"
    class="tab-pane fade show active"
    role="tabpanel"
    aria-labelledby="tab-customer-info"
>
    <h2 class="h3 fw-bold mb-4">{{ __('profile.account_settings') }}</h2>

    <x-profile.nav-tabs
        id="accountTabs"
        orientation="horizontal"
        class="mb-4"
        aria-label="{{ __('profile.account_settings') }}"
        :panes="[
            [
                'id' => 'tab-profile-details',
                'target_id' => '#pane-customer',
                'aria_controls' => 'pane-customer',
                'label' => __('profile.profile_details'),
            ],
            [
                'id' => 'tab-billing',
                'target_id' => '#pane-billing',
                'aria_controls' => 'pane-billing',
                'label' => __('profile.billing'),
            ],
            [
                'id' => 'tab-security',
                'target_id' => '#pane-security',
                'aria_controls' => 'pane-security',
                'label' => __('profile.login_security'),
            ],
        ]"
    />

    <div class="tab-content" id="accountTabContent">
        <x-profile.profile-tab :user="$user" />
        <x-profile.billing-tab :addresses="$addresses" />
        <x-profile.security-tab />
    </div>
</section>
