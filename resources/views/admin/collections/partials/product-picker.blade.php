<section aria-labelledby="collection-picker-heading">
    <h2 id="collection-picker-heading" class="h4">{{ __('promotions.add_products') }}</h2>
    <form
        action="{{ route('admin.collections.edit', $collection) }}"
        method="GET"
        class="d-flex gap-2 my-3"
    >
        <label for="product-search" class="visually-hidden">{{ __('promotions.search_products') }}</label>
        <input
            id="product-search"
            type="search"
            name="q"
            value="{{ $search }}"
            maxlength="100"
            class="form-control rounded-2"
            placeholder="{{ __('promotions.search_products') }}"
        >
        <button type="submit" class="btn btn-outline theme-secondary rounded-2">{{ __('promotions.search') }}</button>
    </form>
    <div class="table-responsive border rounded-2 mb-3">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">{{ __('promotions.products') }}</th>
                    <th scope="col">{{ __('promotions.price_usd') }}</th>
                    <th scope="col">{{ __('promotions.stock') }}</th>
                    <th scope="col">{{ __('promotions.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($candidates as $product)
                    <tr>
                        <th scope="row">
                            {{ $product->name }}
                            <small class="d-block fg-3 fw-normal">{{ $product->vendor?->store_name }}</small>
                        </th>
                        <td>{{ $product->price }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>
                            <form action="{{ route('admin.collections.products.store', $collection) }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->getKey() }}">
                                <button type="submit" class="btn btn-sm btn-outline theme-primary rounded-2">
                                    {{ __('promotions.add') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="fg-3 py-4">{{ __('promotions.no_candidates') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $candidates->links() }}
</section>
