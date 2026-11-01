<section {{ $attributes->class(['p-4']) }} aria-label="{{ __('checkout.account.title') }}">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h5 mb-0">{{ __('checkout.account.title') }}</h2>
        <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="{{ __('common.close') }}"></button>
    </div>
    @auth
        <p class="fw-semibold text-break">{{ auth()->user()->username }}</p>
        <nav class="list-group list-group-flush mb-4" aria-label="{{ __('checkout.account.navigation') }}">
            @foreach ([
                ['label' => __('checkout.account.profile'), 'hash' => 'pane-customer'],
                ['label' => __('checkout.account.stores'), 'hash' => 'pane-vendor'],
                ['label' => __('checkout.account.billing'), 'hash' => 'pane-billing'],
                ['label' => __('checkout.account.password'), 'hash' => 'pane-security'],
            ] as $link)
                <a class="list-group-item list-group-item-action" href="{{ route('profile').'#'.$link['hash'] }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline theme-danger w-100">{{ __('common.logout') }}</button>
        </form>
    @else
        <a class="btn btn-solid theme-primary" href="{{ route('login') }}">{{ __('common.login') }}</a>
    @endauth
</section>
