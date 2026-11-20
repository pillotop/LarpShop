<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\AddCollectionProductRequest;
use App\Http\Requests\Promotions\MoveCollectionProductRequest;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Services\Promotions\ProductCollectionManagementService;
use Illuminate\Http\RedirectResponse;

class CollectionProductController extends Controller
{
    public function store(
        AddCollectionProductRequest $request,
        ProductCollection $collection,
        ProductCollectionManagementService $service,
    ): RedirectResponse {
        $service->addProduct($collection, (int) $request->validated('product_id'));

        return to_route('admin.collections.edit', $collection)->with('success', __('promotions.product_added'));
    }

    public function move(
        MoveCollectionProductRequest $request,
        ProductCollection $collection,
        Product $product,
        ProductCollectionManagementService $service,
    ): RedirectResponse {
        $service->moveProduct($collection, $product->getKey(), $request->validated('direction'));

        return to_route('admin.collections.edit', $collection)->with('success', __('promotions.order_saved'));
    }

    public function destroy(
        ProductCollection $collection,
        Product $product,
        ProductCollectionManagementService $service,
    ): RedirectResponse {
        $service->removeProduct($collection, $product->getKey());

        return to_route('admin.collections.edit', $collection)->with('success', __('promotions.product_removed'));
    }
}
