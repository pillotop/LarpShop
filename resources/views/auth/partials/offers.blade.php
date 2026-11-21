@php
    $slides = config('auth-showcase.slides');
@endphp

<aside class="auth-page-offers" aria-labelledby="auth-offers-title">
    <h2 id="auth-offers-title" class="small fg-2 mb-3">{{ __('auth.page.offers') }}</h2>

    <div
        id="auth-offers-carousel"
        class="carousel carousel-fade auth-offers-carousel"
        role="region"
        aria-roledescription="{{ __('auth_showcase.carousel') }}"
        aria-labelledby="auth-offers-title"
        data-offer-interval="{{ config('auth-showcase.interval') }}"
    >
        <div class="carousel-inner" aria-live="off">
            @foreach ($slides as $slide)
                <div
                    @class(['carousel-item', 'active' => $loop->first])
                    role="group"
                    aria-roledescription="{{ __('auth_showcase.slide') }}"
                    aria-label="{{ __('banners.slide', ['current' => $loop->iteration, 'total' => count($slides)]) }}"
                >
                    <x-auth.offer-art :slide="$slide" :first="$loop->first" />

                    <div class="auth-offer-copy pt-4">
                        <p class="auth-offer-eyebrow small fw-semibold fg-2 mb-2">
                            {{ __('auth_showcase.slides.' . $slide['key'] . '.label') }}
                        </p>
                        <h3 class="h2 fw-semibold mb-3">
                            {{ __('auth_showcase.slides.' . $slide['key'] . '.title') }}
                        </h3>
                        <p class="fg-2 mb-3">{{ __('auth_showcase.slides.' . $slide['key'] . '.description') }}</p>
                        <a href="{{ route('products.index') }}" class="auth-offer-link d-inline-flex align-items-center gap-2">
                            {{ __('auth_showcase.explore') }}
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        @include('auth.partials.offer-controls', ['slides' => $slides])
    </div>
</aside>
