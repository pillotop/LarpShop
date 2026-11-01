@php
    $fields = [
        [
            'name' => 'current_password',
            'label' => __('profile.security.current'),
            'icon' => 'bi-key',
            'autocomplete' => 'current-password',
        ],
        [
            'name' => 'password',
            'label' => __('profile.security.new'),
            'icon' => 'bi-lock',
            'autocomplete' => 'new-password',
            'minlength' => 8,
            'help' => __('profile.security.help'),
        ],
        [
            'name' => 'password_confirmation',
            'label' => __('profile.security.confirm'),
            'icon' => 'bi-lock-fill',
            'autocomplete' => 'new-password',
            'minlength' => 8,
        ],
    ];
@endphp

<div class="tab-pane fade" id="pane-security" role="tabpanel" aria-labelledby="tab-security">
    <div class="card border-0 shadow-sm rounded-9 p-4">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h2 class="h5 fw-bold mb-1 fg-body">{{ __('profile.security.title') }}</h2>
                <p class="fg-3 small mb-0">{{ __('profile.security.intro') }}</p>
            </div>
            <span class="badge bg-subtle-secondary fg-secondary border border-secondary rounded-pill px-3 py-2">
                <i class="bi bi-shield-lock"></i> {{ __('profile.security.badge') }}
            </span>
        </div>

        <form action="{{ route('password.change') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12 md:col-8">
                    @foreach ($fields as $field)
                        <div class="mb-3">
                            <label for="{{ $field['name'] }}" class="form-label fw-semibold">
                                {{ $field['label'] }} <span class="fg-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-1 border-end-0 rounded-start-3">
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
                        <button type="submit" class="btn btn-solid theme-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                            <i class="bi bi-shield-check"></i>
                            <span>{{ __('profile.security.update') }}</span>
                        </button>
                    </div>
                </div>

                <div class="col-12 md:col-4">
                    <div class="p-3 rounded-9 bg-1 border border-secondary h-100">
                        <div class="fw-bold fg-body mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle fg-primary"></i> {{ __('profile.security.tips') }}
                        </div>
                        <ul class="fg-3 small ps-3 mb-0" style="line-height: 1.6;">
                            <li>{{ __('profile.security.no_share') }}</li>
                            <li>{{ __('profile.security.unique') }}</li>
                            <li>{{ __('profile.security.sign_out') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
