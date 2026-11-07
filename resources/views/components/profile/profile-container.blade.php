@props([
    'account',
    'allowEdit' => false,
    'editBtnEvent' => '',
])

@vite('resources/css/components/profile_container.css')

<div
    {{
        $attributes->class([
            'profile-container',
            'rounded-circle',
            'position-relative',
            'overflow-hidden',
        ])
    }}
    @if ($allowEdit)
        x-data
    @endif
>
    <img
        class="profile-image w-100 h-100 object-fit-cover"
        src="{{ $account->profile_image_url }}"
        alt="{{ __('profile.profile_container.alt_text') }}"
        style="width: 76px; height: 76px;"
    />

    @if ($allowEdit)
    <button
        type="button"
        class="profile-edit-button d-flex align-items-center justify-content-center"
        x-on:click="{{ $editBtnEvent }}"
        aria-label="{{ __('profile.profile_container.edit_button_aria') }}"
    >
        <i class="bi bi-camera"></i>
    </button>
    @endif
</div>
