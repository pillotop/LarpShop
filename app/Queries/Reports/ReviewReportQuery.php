<?php

namespace App\Queries\Reports;

use App\Data\Reports\ReportPeriod;
use App\Models\ProductReview;

class ReviewReportQuery
{
    public function overview(ReportPeriod $period): array
    {
        $reviews = ProductReview::query()
            ->when($period->since, fn ($query, $since) => $query->where('created_at', '>=', $since))
            ->where('created_at', '<=', $period->until);
        $counts = (clone $reviews)->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $total = (int) $counts->sum();
        $ratings = [];
        $weightedTotal = 0;
        foreach (range(5, 1) as $rating) {
            $count = (int) ($counts[$rating] ?? 0);
            $ratings[] = ['rating' => $rating, 'count' => $count, 'percent' => $total > 0 ? round($count / $total * 100, 1) : 0];
            $weightedTotal += $rating * $count;
        }

        return [
            'total' => $total,
            'average' => $total > 0 ? round($weightedTotal / $total, 2) : null,
            'low_ratings' => (int) ($counts[1] ?? 0) + (int) ($counts[2] ?? 0),
            'ratings' => $ratings,
            'recent_low_ratings' => (clone $reviews)->where('rating', '<=', 2)
                ->with('product:product_id,name')
                ->latest('created_at')->orderByDesc('review_id')->limit(5)
                ->get(['review_id', 'product_id', 'rating', 'comment', 'created_at']),
        ];
    }
}
