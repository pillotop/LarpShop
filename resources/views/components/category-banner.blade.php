@props([
    'title',
    'slug',
    'image',
    'alt',
    'ratio' => 'ratio-1x1',
    'customRatio' => null,
])

<a
    href="{{ route('categories.show', ['slug' => $slug]) }}"
    class="category-banner d-block position-relative overflow-hidden rounded-1 h-100"
>
    <div
        class="h-100 ratio {{ $customRatio ? '' : $ratio }}"
        @if ($customRatio)
            style="--bs-aspect-ratio: {{ $customRatio }};"
        @endif
    >
        <img
            src="{{ $image }}"
            alt="{{ $alt }}"
            class="w-100 h-100 object-fit-cover"
        >
    </div>

    <div class="category-banner__overlay position-absolute bottom-0 start-0 w-100 p-3">
        <h2 class="h5 mb-1">{{ $title }}</h2>
        <span class="category-banner__action">
            Explore collection
        </span>
    </div>
</a>
