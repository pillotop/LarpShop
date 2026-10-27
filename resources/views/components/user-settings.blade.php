<section {{ $attributes->class(['p-4']) }} aria-label="Account settings">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h5 mb-0">Account settings</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    @auth
        <p class="fw-semibold text-break">{{ auth()->user()->username }}</p>
        <nav class="list-group list-group-flush mb-4" aria-label="Account navigation">
            @foreach ([
                ['label' => 'Personal profile', 'hash' => 'pane-customer'],
                ['label' => 'My stores', 'hash' => 'pane-vendor'],
                ['label' => 'Billing & addresses', 'hash' => 'pane-billing'],
                ['label' => 'Change password', 'hash' => 'pane-security'],
            ] as $link)
                <a class="list-group-item list-group-item-action" href="{{ route('profile').'#'.$link['hash'] }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-danger w-100">Sign out</button>
        </form>
    @else
        <a class="btn btn-primary" href="{{ route('login') }}">Sign in</a>
    @endauth
</section>
