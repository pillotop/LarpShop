<div class="broad-select-choices">
    <ul
        class="selector-category-list"
        role="tablist"
        aria-label="{{ __('promotions.product_groups') }}"
        aria-orientation="horizontal"
    >
        @foreach ($groups as $group)
            <li role="presentation">
                <button
                    id="{{ $instanceId }}-tab-{{ $loop->index }}"
                    type="button"
                    role="tab"
                    @class(['selector-item', 'is-active' => $loop->first])
                    aria-controls="{{ $instanceId }}-panel-{{ $loop->index }}"
                    data-broad-select-mode="{{ $group->key }}"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    tabindex="{{ $loop->first ? '0' : '-1' }}"
                >
                    {{ $group->label }}
                </button>
            </li>
        @endforeach
    </ul>
</div>
