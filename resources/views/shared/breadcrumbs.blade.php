@unless ($breadcrumbs->isEmpty())
    <nav aria-label="{{ __('common.breadcrumb') }}">
        <ol class="breadcrumb flex-wrap align-items-center mb-0">
            @foreach ($breadcrumbs as $breadcrumb)
                <li class="breadcrumb-item align-items-center">
                    @unless ($loop->first)
                        <span class="breadcrumb-divider" aria-hidden="true"></span>
                    @endunless
                    @if ($breadcrumb->url && ! $loop->last)
                        <a class="breadcrumb-link" href="{{ $breadcrumb->url }}">{{ $breadcrumb->title }}</a>
                    @else
                        <span class="breadcrumb-link active" aria-current="page">{{ $breadcrumb->title }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endunless
