@props([
    'borderRadius' => '5',
])

<div
    class="rounded-top-{{ $borderRadius }} mt-4 py-4"
    style="background-color: var(--color-complementary-200);"
>
    {{ $slot }}
</div>
