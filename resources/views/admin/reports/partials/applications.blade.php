@php
    $chart = [
        'type' => 'bar',
        'integer' => true,
        'labels' => array_map(fn ($status) => __('management.report_operations.application_states.'.$status), array_keys($applicationReport['statuses'])),
        'datasets' => [['label' => __('management.report_operations.applications'), 'data' => array_values($applicationReport['statuses'])]],
    ];
@endphp

<section class="card p-4 h-100" data-report-chart="{{ json_encode($chart) }}">
    <h3 class="h6 fw-bold">{{ __('management.report_operations.applications') }}</h3>
    <p class="small fg-2">{{ __('management.report_operations.applications_note') }}</p>
    <p class="small fw-semibold">{{ __('management.report_operations.submitted_count', ['count' => number_format($applicationReport['submitted'])]) }}</p>
    @if ($applicationReport['submitted'] > 0)
        <div class="admin-report-timeseries position-relative mb-3" data-chart-visual hidden>
            <canvas role="img" aria-label="{{ __('management.report_operations.applications') }}"></canvas>
        </div>
    @endif
    <dl class="row small mb-3">
        @foreach ($applicationReport['statuses'] as $status => $count)
            <dt class="col-8 fw-normal">{{ __('management.report_operations.application_states.'.$status) }}</dt>
            <dd class="col-4 text-end">{{ number_format($count) }}</dd>
        @endforeach
        <dt class="col-8 fw-normal">{{ __('management.report_operations.review_hours') }}</dt>
        <dd class="col-4 text-end">
            {{ $applicationReport['average_review_hours'] === null ? '—' : number_format($applicationReport['average_review_hours'], 1) }}
        </dd>
    </dl>
    <p class="small fg-2">{{ __('management.report_operations.review_samples', ['count' => number_format($applicationReport['review_samples'])]) }}</p>
    <h4 class="h6 small fw-semibold">{{ __('management.report_operations.application_queue', ['count' => number_format($applicationReport['pending_now'])]) }}</h4>
    <p class="small fg-2">{{ __('management.report_operations.application_queue_note') }}</p>
    <ul class="list-unstyled small mb-3">
        @forelse ($applicationReport['oldest_pending'] as $application)
            <li class="border-top py-2">
                @if ($application->vendor_id)
                    <a href="{{ route('admin.vendors.show', $application->vendor_id) }}" class="fg-body">{{ $application->store_name }}</a>
                @else
                    <span>{{ $application->store_name }}</span>
                @endif
                <span class="d-block fg-2">{{ $application->submitted_at->format('Y-m-d H:i') }}</span>
            </li>
        @empty
            <li class="fg-2">{{ __('management.report_operations.no_application_queue') }}</li>
        @endforelse
    </ul>
    <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="small fg-body">
        {{ __('management.report_operations.legacy_pending', ['count' => number_format($applicationReport['legacy_pending'])]) }}
        <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i>
    </a>
</section>
