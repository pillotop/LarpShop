<div class="category-menu-layout container">
    <nav class="category-menu-navigation" aria-label="{{ __('common.categories') }}">
        <div class="category-menu-toolbar">
            <span class="small fg-3">{{ __('category_menu.scope_hint') }}</span>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary rounded-2"
                data-category-reset
            >{{ __('category_menu.all') }}</button>
        </div>
        <x-two-arrow-scroller
            class="category-menu-scroller"
            :content-id="$contentId"
            :plain-arrows="true"
        >
            <ul
                id="{{ $contentId }}"
                class="category-menu-groups"
                data-scroll-content
                tabindex="0"
                aria-label="{{ __('common.categories') }}"
            >
                @foreach ($groups as $group)
                    @include('components.category-dropdown.group')
                @endforeach
                @foreach ($fallbackCategories as $category)
                    <li class="category-menu-group">
                        @include('components.category-dropdown.item', ['isParent' => false])
                    </li>
                @endforeach
                @if ($groups->isEmpty() && $fallbackCategories->isEmpty())
                    <li class="category-menu-empty">{{ __('category_menu.empty') }}</li>
                @endif
            </ul>
        </x-two-arrow-scroller>
    </nav>
    <aside class="category-menu-preview" aria-label="{{ __('category_menu.explore') }}">
        @if ($groups->isNotEmpty() || $fallbackCategories->isNotEmpty())
            @include('components.category-dropdown.preview')
        @else
            <p class="small fg-3 mb-0">{{ __('category_menu.explore_hint') }}</p>
        @endif
    </aside>
</div>
