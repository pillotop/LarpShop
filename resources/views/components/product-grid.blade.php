@props([
    'products' => [],
    'skeletonCount' => 0,
    'columns' => ['default' => 2, 'md' => 3, 'lg' => 4, '2xl' => 6],
])

<x-grid :columns="$columns" gap="3" {{ $attributes }}>
    @if ($skeletonCount > 0)
        @for ($index = 0; $index < $skeletonCount; $index++)
            <div class="col">
                <x-product-card-skeleton />
            </div>
        @endfor
    @else
        @foreach ($products as $product)
            <div class="col">
                <x-product-card-b :product="$product" class="h-100" />
            </div>
        @endforeach
    @endif
</x-grid>
