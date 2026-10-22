@php
    $fields = [
        [
            'name' => 'current_password',
            'label' => 'Current password',
            'icon' => 'bi-key',
            'autocomplete' => 'current-password',
        ],
        [
            'name' => 'password',
            'label' => 'New password',
            'icon' => 'bi-lock',
            'autocomplete' => 'new-password',
            'minlength' => 8,
            'help' => 'Use at least 8 characters, combining letters and numbers for better security.',
        ],
        [
            'name' => 'password_confirmation',
            'label' => 'Confirm new password',
            'icon' => 'bi-lock-fill',
            'autocomplete' => 'new-password',
            'minlength' => 8,
        ],
    ];
@endphp

<div class="tab-pane fade" id="pane-security" role="tabpanel" aria-labelledby="tab-security">
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h2 class="h5 fw-bold mb-1 text-dark">Change account password</h2>
                <p class="text-muted small mb-0">Choose a password with at least 8 characters to protect your account.</p>
            </div>
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2">
                <i class="bi bi-shield-lock"></i> Security
            </span>
        </div>

        <form action="{{ route('password.change') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12 col-md-8">
                    @foreach ($fields as $field)
                        <div class="mb-3">
                            <label for="{{ $field['name'] }}" class="form-label fw-semibold">
                                {{ $field['label'] }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 rounded-start-3">
                                    <i class="bi {{ $field['icon'] }}"></i>
                                </span>
                                <input
                                    type="password"
                                    class="form-control rounded-end-3 @error($field['name']) is-invalid @enderror"
                                    id="{{ $field['name'] }}"
                                    name="{{ $field['name'] }}"
                                    required
                                    @if (isset($field['minlength'])) minlength="{{ $field['minlength'] }}" @endif
                                    autocomplete="{{ $field['autocomplete'] }}"
                                >
                                @error($field['name'])
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if (isset($field['help']))
                                <div class="form-text small">{{ $field['help'] }}</div>
                            @endif
                        </div>
                    @endforeach

                    <div class="mt-4 pt-3 border-top d-flex justify-content-start">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                            <i class="bi bi-shield-check"></i>
                            <span>Update password</span>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="p-3 rounded-4 bg-light border border-light-subtle h-100">
                        <div class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle text-primary"></i> Security tips
                        </div>
                        <ul class="text-muted small ps-3 mb-0" style="line-height: 1.6;">
                            <li>Never share your password with anyone.</li>
                            <li>Use a unique password for this account.</li>
                            <li>Always sign out after using a shared device.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
