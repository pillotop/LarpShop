<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorReviewReplyController extends Controller
{
    public function store(Request $request, ProductReview $review): RedirectResponse
    {
        $review->loadMissing('product');
        $product = $review->product;

        abort_unless(
            $product !== null && $request->user()->vendors()->active()->where('vendor_id', $product->vendor_id)->exists(),
            403,
        );

        $validated = $request->validate([
            'vendor_reply' => ['required', 'string', 'max:1000'],
        ]);

        $review->update([
            'vendor_reply' => trim($validated['vendor_reply']),
            'vendor_replied_at' => now(),
        ]);

        return back()->withFragment('product-reviews')
            ->with('review_success', __('reviews.reply_saved'));
    }
}
