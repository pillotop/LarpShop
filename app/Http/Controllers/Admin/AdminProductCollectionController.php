<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\CollectionSearchRequest;
use App\Http\Requests\Promotions\SaveProductCollectionRequest;
use App\Models\ProductCollection;
use App\Queries\Products\ProductCatalogQuery;
use App\Queries\Promotions\ProductCollectionQuery;
use App\Services\Promotions\ProductCollectionManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminProductCollectionController extends Controller
{
    public function index(CollectionSearchRequest $request, ProductCollectionQuery $collections): View
    {
        $search = trim($request->validated('q') ?? '');

        return view('admin.collections.index', ['collections' => $collections->forAdmin($search), 'search' => $search]);
    }

    public function create(): View
    {
        return view('admin.collections.create', ['collection' => new ProductCollection]);
    }

    public function store(SaveProductCollectionRequest $request, ProductCollectionManagementService $service): RedirectResponse
    {
        $collection = $service->save(null, $request->validated());

        return to_route('admin.collections.edit', $collection)->with('success', __('promotions.saved'));
    }

    public function edit(
        CollectionSearchRequest $request,
        ProductCollection $collection,
        ProductCollectionQuery $collections,
        ProductCatalogQuery $products,
    ): View {
        $search = trim($request->validated('q') ?? '');

        return view('admin.collections.edit', [
            'collection' => $collection,
            'members' => $collections->members($collection),
            'candidates' => $products->collectionCandidates($collection, $search),
            'search' => $search,
        ]);
    }

    public function update(
        SaveProductCollectionRequest $request,
        ProductCollection $collection,
        ProductCollectionManagementService $service,
    ): RedirectResponse {
        $service->save($collection, $request->validated());

        return to_route('admin.collections.edit', $collection)->with('success', __('promotions.saved'));
    }

    public function destroy(ProductCollection $collection, ProductCollectionManagementService $service): RedirectResponse
    {
        $service->delete($collection);

        return to_route('admin.collections.index')->with('success', __('promotions.deleted'));
    }
}
