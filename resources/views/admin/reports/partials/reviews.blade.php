@php
    $chart = [
        'type' => 'bar',
        'integer' => true,
        'labels' => array_map(fn ($row) => trans_choice('reviews.stars', $row['rating'], ['count' => $row['rating']]), $reviewReport['ratings']),
        'datasets' => [['label' => __('management.report_operations.reviews'), 'data' => array_column($reviewReport['ratings'], 'count')]],
    ];
@endphp

<section class="card p-4 h-100" data-report-chart="{{ json_encode($chart) }}">
    <h3 class="h6 fw-bold">{{ __('management.report_operations.reviews') }}</h3>
    <p class="small fg-2">{{ __('management.report_operations.reviews_note') }}</p>
    <p class="small fw-semibold">
        {{ __('management.report_operations.reviews_summary', [
            'count' => number_format($reviewReport['total']),
            'average' => $reviewReport['average'] === null ? '—' : number_format($reviewReport['average'], 2),
            'low' => number_format($reviewReport['low_ratings']),
        ]) }}
    </p>
    @if ($reviewReport['total'] > 0)
        <div class="admin-report-timeseries position-relative mb-3" data-chart-visual hidden>
            <canvas role="img" aria-label="{{ __('management.report_operations.reviews') }}"></canvas>
        </div>
    @endif
    <div class="table-responsive mb-3">
        <table class="table small mb-0" aria-label="{{ __('management.report_operations.reviews') }}">
            <thead>
                <tr>
                    <th scope="col">{{ __('management.report_operations.rating') }}</th>
                    <th scope="col" class="text-end">{{ __('management.report_operations.reviews') }}</th>
                    <th scope="col" class="text-end">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reviewReport['ratings'] as $row)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <a href="{{ route('admin.reviews.index', ['rating' => $row['rating']]) }}" class="fg-body">
                                {{ trans_choice('reviews.stars', $row['rating'], ['count' => $row['rating']]) }}
                            </a>
                        </th>
                        <td class="text-end">{{ number_format($row['count']) }}</td>
                        <td class="text-end">{{ $row['percent'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <h4 class="h6 small fw-semibold">{{ __('management.report_operations.recent_low_reviews') }}</h4>
    <p class="small fg-2">{{ __('management.report_operations.review_links_note') }}</p>
    <ul class="list-unstyled small mb-0">
        @forelse ($reviewReport['recent_low_ratings'] as $review)
            <li class="border-top py-2">
                <span class="fw-semibold">{{ $review->product->name }}</span>
                <span class="fg-2"> · {{ $review->rating }}/5 · {{ $review->created_at->format('Y-m-d') }}</span>
                @if ($review->comment)
                    <p class="fg-2 mb-0">{{ str($review->comment)->limit(140) }}</p>
                @endif
            </li>
        @empty
            <li class="fg-2">{{ __('management.report_operations.no_low_reviews') }}</li>
        @endforelse
    </ul>
</section>
