<section class="mb-5" aria-labelledby="collection-members-heading">
    <h2 id="collection-members-heading" class="h4">{{ __('promotions.selected_products') }}</h2>
    <p class="small fg-3">{{ __('promotions.members_hint', ['count' => config('promotions.max_collection_products')]) }}</p>
    <div class="table-responsive border ">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">{{ __('promotions.position') }}</th>
                    <th scope="col">{{ __('promotions.products') }}</th>
                    <th scope="col">{{ __('promotions.stock') }}</th>
                    <th scope="col">{{ __('promotions.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $product)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <th scope="row">
                            {{ $product->name }}
                            <small class="d-block fg-3 fw-normal">{{ $product->vendor?->store_name }}</small>
                        </th>
                        <td>{{ $product->stock }}</td>
                        <td class="text-nowrap">
                            @foreach (['up', 'down'] as $direction)
                                <form
                                    action="{{ route('admin.collections.products.move', [$collection, $product]) }}"
                                    method="POST"
                                    class="d-inline"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline theme-inverse "
                                        aria-label="{{ __('promotions.move_'.$direction, ['name' => $product->name]) }}"
                                        @disabled(($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last))
                                    ><i class="bi bi-arrow-{{ $direction }}" aria-hidden="true"></i></button>
                                </form>
                            @endforeach
                            <form
                                action="{{ route('admin.collections.products.destroy', [$collection, $product]) }}"
                                method="POST"
                                class="d-inline"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline theme-inverse "
                                    aria-label="{{ __('promotions.remove_product', ['name' => $product->name]) }}"
                                >{{ __('promotions.remove') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="fg-3 py-4">{{ __('promotions.no_members') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
