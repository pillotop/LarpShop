@props([
    'user',
    'vendors',
])

<section class="profile-hero p-4 md:p-5 mb-4 shadow">
    <div class="row align-items-center g-4">
        <div class="col-auto">
            <div class="avatar-circle">
                {{ strtoupper(substr($user->username, 0, 1)) }}
            </div>
        </div>
        <div class="col">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h1 class="h2 fw-bold mb-0">{{ $user->username }}</h1>
                @if ($user->isAdmin())
                    <span class="badge bg-danger fg-white px-3 py-1 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-shield-lock-fill me-1"></i> {{ __('profile.role.admin') }}
                    </span>
                @else
                    <span class="badge bg-1 fg-primary px-3 py-1 rounded-pill fw-semibold">
                        <i class="bi bi-person-fill me-1"></i> {{ __('profile.role.customer') }}
                    </span>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-3 fg-white fg-50 mt-2 small">
                <div>
                    <i class="bi bi-envelope me-1 fg-white"></i> {{ $user->email }}
                </div>
                @if ($user->phone_number)
                    <div>
                        <i class="bi bi-telephone me-1 fg-white"></i> {{ $user->phone_number }}
                    </div>
                @endif
                <div>
                    <i class="bi bi-calendar-event me-1 fg-white"></i> {{ __('profile.member_since', ['date' => $user->created_at ? $user->created_at->format('d/m/Y') : __('profile.recent_member')]) }}
                </div>
            </div>
        </div>
        @if ($user->isAdmin())
            <div class="col-12 md:col-auto">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-solid theme-secondary rounded-pill px-4 py-2 fw-semibold fg-danger shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-speedometer2"></i>
                    <span>{{ __('common.admin_dashboard') }}</span>
                </a>
            </div>
        @elseif ($vendors->isNotEmpty())
            <div class="col-12 md:col-auto">
                <a href="{{ route('profile') }}#pane-vendor" class="btn btn-solid theme-secondary rounded-pill px-4 py-2 fw-semibold fg-primary shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-shop"></i>
                    <span>{{ __('profile.manage_stores') }}</span>
                </a>
            </div>
        @endif
    </div>
</section>
