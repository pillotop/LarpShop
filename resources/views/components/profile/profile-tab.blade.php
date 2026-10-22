@props([
    'user',
])

@php
    $credentials = [
        [
            'prop' => 'username',
            'display1' => 'Username',
            'display2' => 'Username',
            'icon' => 'bi-at',
            'type' => 'text',
            'required' => true,
            'minlength' => 3,
            'maxlength' => 50,
            'help' => 'Your username is used to sign in and identify you on LarpShop.',
        ],
        [
            'prop' => 'email',
            'display1' => 'Account email',
            'display2' => 'Email address',
            'icon' => 'bi-envelope',
            'type' => 'email',
            'required' => true,
            'maxlength' => 100,
            'help' => 'Used for order notifications and account security updates.',
        ],
        [
            'prop' => 'phone_number',
            'display1' => 'Phone number',
            'display2' => 'Contact phone number',
            'icon' => 'bi-telephone',
            'type' => 'text',
            'required' => false,
            'maxlength' => 15,
            'placeholder' => 'Example: 0912345678',
            'fallback' => 'Not provided',
            'help' => 'Used for deliveries and customer support.',
        ],
    ];
    $roleLabel = $user->isAdmin() ? 'Administrator' : 'Customer';
@endphp

<div class="tab-pane fade show active" id="pane-customer" role="tabpanel" aria-labelledby="tab-customer-info">
    {{-- Quick Stats / Info Cards --}}
    <div class="row g-3 mb-4">
        @foreach ($credentials as $cred)
            <div class="col-sm-6 col-md-4">
                <div class="stat-card p-3 h-100">
                    <div class="text-muted small fw-semibold">{{ $cred['display1'] }}</div>
                    <div class="fs-5 fw-bold text-dark mt-1 text-truncate">
                        {{ $user->{$cred['prop']} ?: ($cred['fallback'] ?? '') }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Update Profile Form --}}
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h2 class="h5 fw-bold mb-1 text-dark">Update personal information</h2>
                <p class="text-muted small mb-0">Manage your username, email address, and contact phone number.</p>
            </div>
            @if ($user->isAdmin())
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                    <i class="bi bi-shield-lock-fill me-1"></i> {{ $roleLabel }}
                </span>
            @else
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">
                    <i class="bi bi-person me-1"></i> {{ $roleLabel }}
                </span>
            @endif
        </div>

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                @foreach ($credentials as $cred)
                    <div class="col-md-6">
                        <label for="{{ $cred['prop'] }}" class="form-label fw-semibold">
                            {{ $cred['display2'] }}
                            @if ($cred['required'])
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3">
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

                <div class="col-md-6">
                    <label for="account_role" class="form-label fw-semibold">Account role</label>
                    <input type="text" class="form-control rounded-3 bg-light" id="account_role" value="{{ $roleLabel }}" disabled>
                    <div class="form-text small">Your role is managed automatically by LarpShop.</div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-check2-circle"></i>
                    <span>Save profile changes</span>
                </button>
            </div>
        </form>
    </div>
</div>
