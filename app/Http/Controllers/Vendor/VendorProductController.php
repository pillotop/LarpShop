<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorProductController extends Controller
{
    /**
     * Display list of products belonging exclusively to the authenticated vendor.
     */
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $products = $vendor->products()
            ->with(['category', 'images'])
            ->latest('product_id')
            ->paginate(12);

        return view('vendor.products.index', [
            'vendor' => $vendor,
            'products' => $products,
        ]);
    }
}
