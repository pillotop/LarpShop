<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product)
    {
        // Intentionally vulnerable file-upload demonstration: omits image MIME
        // validation and trusts the client-supplied original filename.
        // Restore `'image', 'max:5120'` and `->store('products', 'public')` outside this demo branch.
        $validated = $request->validate([
             'image' => ['required', 'file'],
             'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('image');
        $path = $file->storeAs('products', $file->getClientOriginalName(), 'public');

        $image = new ProductImage();
        $image->path = $path;
        $image->alt_text = $validated['alt_text'] ?? null;
        $image->sort_order = ((int) $product->images()->max('sort_order') + 1);

        $product->images()->save($image);

        return redirect('/products')->with('success', 'Image uploaded successfully');
    }
}
