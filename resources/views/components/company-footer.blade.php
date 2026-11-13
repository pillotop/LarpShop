<footer class="blur company-footer position-relative fixed-bottom d-block w-100 border-top-dashed border-start-dashed border-end-dashed border-thin p-4">
    <div class="container">
        <div class="row row-cols-2 lg:row-cols-3 w-100 my-4">
            @foreach ($sections as $section)
                <div class="col">
                    <h4 class="text-uppercase">
                        {{ __($section['title']) }}
                    </h4>

                    <ul class="list-unstyled">
                        @foreach ($section['links'] as $link)
                            <li class="my-3">
                                <a
                                    href="{{ $link['url'] }}"
                                    class="text-capitalize text-decoration-none"
                                >
                                    {{ __($link['label']) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 md:gap-5">
            <button
                type="button"
                class="hover-button p-2 border-0 rounded-5"
                data-locale-panel
                data-bs-toggle="drawer"
                data-bs-target="#language-panel"
                aria-haspopup="dialog"
                aria-controls="language-panel"
            >
                {{ __('common.language') }}
            </button>

            @foreach ($legalLinks as $link)
                <a href="{{ $link['url'] }}" class="text-decoration-none">
                    {{ __($link['label']) }}
                </a>
            @endforeach
        </div>

        <div class="text-center mt-4">
            &copy; {{ date('Y') }} LarpShop
        </div>
    </div>
</footer>

<x-locale-panels />
