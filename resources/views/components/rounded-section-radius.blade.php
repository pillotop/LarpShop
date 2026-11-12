@props([
    'borderRadius' => '5',
])

<div
    class="rounded-top-{{ $borderRadius }} mt-4 py-4"
    style="background-color: var(--color-complementary-200); color-scheme: light; color: #111111;"
>
    {{ $slot }}
</div>
