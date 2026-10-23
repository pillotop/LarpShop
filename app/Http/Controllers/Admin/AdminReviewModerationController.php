<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReviewModerationController extends Controller
{
    /**
     * UC-43: List all reviews with rating filter and search by comment, reviewer, or product.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $rating = $validated['rating'] ?? null;

        $query = ProductReview::query()
            ->with(['user', 'product.vendor'])
            ->latest('review_id');

        if ($keyword !== '') {
            $escaped = addcslashes($keyword, '\\%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('comment', 'like', "%{$escaped}%")
                    ->orWhereHas('user', function ($u) use ($escaped) {
                        $u->where('username', 'like', "%{$escaped}%")
                            ->orWhere('email', 'like', "%{$escaped}%");
                    })
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$escaped}%"));
            });
        }

        if ($rating !== null) {
            $query->where('rating', $rating);
        }

        return view('admin.reviews.index', [
            'reviews' => $query->paginate(15)->withQueryString(),
            'keyword' => $keyword,
            'rating' => $rating,
        ]);
    }

    /**
     * UC-43: Permanently remove an inappropriate or fraudulent review.
     */
    public function destroy(ProductReview $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', "Review #{$review->review_id} has been removed.");
    }
}
