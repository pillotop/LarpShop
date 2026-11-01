@props([
    'user',
])

@php
    $credentials = [
        [
            'prop' => 'username',
            'display1' => __('profile.username'),
            'display2' => __('profile.username'),
            'icon' => 'bi-at',
            'type' => 'text',
            'required' => true,
            'minlength' => 3,
            'maxlength' => 50,
            'help' => __('profile.username_help'),
        ],
        [
            'prop' => 'email',
            'display1' => __('profile.account_email'),
            'display2' => __('profile.email'),
            'icon' => 'bi-envelope',
            'type' => 'email',
            'required' => true,
            'maxlength' => 100,
            'help' => __('profile.email_help'),
        ],
        [
            'prop' => 'phone_number',
            'display1' => __('profile.phone'),
            'display2' => __('profile.contact_phone'),
            'icon' => 'bi-telephone',
            'type' => 'text',
            'required' => false,
            'maxlength' => 15,
            'placeholder' => __('profile.phone_example'),
            'fallback' => __('profile.not_provided'),
            'help' => __('profile.phone_help'),
        ],
    ];
    $roleLabel = $user->isAdmin() ? __('profile.role.admin') : __('profile.role.customer');
@endphp

<div class="tab-pane fade show active" id="pane-customer" role="tabpanel" aria-labelledby="tab-customer-info">
    {{-- Quick Stats / Info Cards --}}
    <div class="row g-3 mb-4">
        @foreach ($credentials as $cred)
            <div class="sm:col-6 md:col-4">
                <div class="stat-card p-3 h-100">
                    <div class="fg-3 small fw-semibold">{{ $cred['display1'] }}</div>
                    <div class="fs-lg fw-bold fg-body mt-1 text-truncate">
                        {{ $user->{$cred['prop']} ?: ($cred['fallback'] ?? '') }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Update Profile Form --}}
    <div class="card border-0 shadow-sm rounded-9 p-4">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h2 class="h5 fw-bold mb-1 fg-body">{{ __('profile.update_title') }}</h2>
                <p class="fg-3 small mb-0">{{ __('profile.update_intro') }}</p>
            </div>
            @if ($user->isAdmin())
                <span class="badge bg-subtle-danger fg-danger border border-danger rounded-pill px-3 py-2">
                    <i class="bi bi-shield-lock-fill me-1"></i> {{ $roleLabel }}
                </span>
            @else
                <span class="badge bg-subtle-primary fg-primary border border-primary rounded-pill px-3 py-2">
                    <i class="bi bi-person me-1"></i> {{ $roleLabel }}
                </span>
            @endif
        </div>

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                @foreach ($credentials as $cred)
                    <div class="md:col-6">
                        <label for="{{ $cred['prop'] }}" class="form-label fw-semibold">
                            {{ $cred['display2'] }}
                            @if ($cred['required'])
                                <span class="fg-danger">*</span>
                            @endif
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-1 border-end-0 rounded-start-3">
                                <i class="bi {{ $cred['icon'] }}"></i>
                            </span>
                            <input
                                type="{{ $cred['type'] }}"
                                class="form-control rounded-end-3 @error($cred['prop']) is-invalid @enderror"
                                id="{{ $cred['prop'] }}"
                                name="{{ $cred['prop'] }}"
                                value="{{ old($cred['prop'], $user->{$cred['prop']}) }}"
                                @required($cred['required'])
                                @if (isset($cred['minlength'])) minlength="{{ $cred['minlength'] }}" @endif
                                maxlength="{{ $cred['maxlength'] }}"
                                @if (isset($cred['placeholder'])) placeholder="{{ $cred['placeholder'] }}" @endif
                            >
                            @error($cred['prop'])
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text small">{{ $cred['help'] }}</div>
                    </div>
                @endforeach

                <div class="md:col-6">
                    <label for="account_role" class="form-label fw-semibold">{{ __('profile.account_role') }}</label>
                    <input type="text" class="form-control rounded-5 bg-1" id="account_role" value="{{ $roleLabel }}" disabled>
                    <div class="form-text small">{{ __('profile.role_help') }}</div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                <button type="submit" class="btn btn-solid theme-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-check2-circle"></i>
                    <span>{{ __('profile.save') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
