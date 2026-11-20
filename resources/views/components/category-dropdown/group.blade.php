<li class="category-menu-group" tabindex="0">
    <h2 class="category-menu-heading">
        @include('components.category-dropdown.item', ['category' => $group['category'], 'isParent' => true])
    </h2>
    @if ($group['children']->isNotEmpty())
        <ul class="category-menu-children">
            @foreach ($group['children'] as $child)
                <li>
                    @include('components.category-dropdown.item', ['category' => $child, 'isParent' => false])
                </li>
            @endforeach
        </ul>
    @endif
</li>
