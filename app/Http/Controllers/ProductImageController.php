<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
             'image' => ['required', 'image', 'max:5120'],
             'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image')->store('products', 'public');

        $image = new ProductImage();
        $image->path = $path;
        $image->alt_text = $validated['alt_text'] ?? null;
        $image->sort_order = ((int) $product->images()->max('sort_order') + 1);

        $product->images()->save($image);

        return redirect('/products')->with('success', 'Image uploaded successfully');
    }
}
