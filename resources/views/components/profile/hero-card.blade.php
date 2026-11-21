@props([
    'user',
    'vendors',
])

<section class="profile-hero border rounded-2 mb-4">
    <div class="profile-hero-cover" aria-hidden="true"></div>

    <div class="profile-hero-body">
        <div class="profile-hero-avatar">
            <x-profile.profile-container :account="$user" />
        </div>

        <div class="profile-hero-info">
            <h1 class="h2 fw-bold mb-3">{{ $user->username }}</h1>
            <ul class="profile-hero-details list-unstyled small mb-0">
                <li>
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <span>{{ $user->email }}</span>
                </li>
                @if ($user->phone_number)
                    <li>
                        <i class="bi bi-telephone" aria-hidden="true"></i>
                        <span>{{ $user->phone_number }}</span>
                    </li>
                @endif
                <li>
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <span>
                        {{ __('profile.member_since', ['date' => $user->created_at ? $user->created_at->format('d/m/Y') : __('profile.recent_member')]) }}
                    </span>
                </li>
            </ul>
        </div>

        <div class="profile-hero-actions">
            <span class="badge bg-subtle-primary fg-primary border border-primary rounded-pill px-3 py-2">
                <i
                    class="bi {{ $user->isAdmin() ? 'bi-shield-lock' : 'bi-person' }} me-1"
                    aria-hidden="true"
                ></i>
                {{ $user->isAdmin() ? __('profile.role.admin') : __('profile.role.customer') }}
            </span>

            @if ($user->isAdmin())
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="btn btn-outline theme-primary rounded-2 d-inline-flex align-items-center gap-2"
                >
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>
                    <span>{{ __('common.admin_dashboard') }}</span>
                </a>
            @elseif ($vendors->isNotEmpty())
                <a
                    href="{{ route('profile') }}#pane-vendor"
                    class="btn btn-outline theme-primary rounded-2 d-inline-flex align-items-center gap-2"
                >
                    <i class="bi bi-shop" aria-hidden="true"></i>
                    <span>{{ __('profile.manage_stores') }}</span>
                </a>
            @endif
        </div>
    </div>
</section>
