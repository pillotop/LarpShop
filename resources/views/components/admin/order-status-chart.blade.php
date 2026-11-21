@props(['distribution', 'total'])

<section class="card p-4 mb-4" data-order-status-chart>
    <h2 class="h6 fw-bold mb-3">{{ __('management.order_status_distribution') }}</h2>

    @if ($total === 0)
        <p class="fg-3 small mb-0">{{ __('management.no_orders_in_this_period') }}</p>
    @else
        <div class="row g-4 align-items-center">
            <div class="col-12 md:col-5">
                <div class="admin-report-chart position-relative mx-auto" hidden data-chart-visual>
                    <canvas
                        role="img"
                        aria-label="{{ __('management.order_status_distribution') }}"
                    ></canvas>
                    <div class="admin-report-chart-total position-absolute text-center" aria-hidden="true">
                        <strong class="d-block fs-lg">{{ number_format($total) }}</strong>
                        <span class="small fg-2">{{ __('management.total_orders') }}</span>
                    </div>
                </div>
            </div>
            <div class="col-12 md:col-7">
                <p class="small fg-2" hidden data-chart-help>{{ __('management.report_copy.chart_help') }}</p>
                <ul class="list-unstyled mb-3">
                    @foreach ($distribution as $status => $data)
                        <li
                            class="d-flex align-items-center gap-3 border-bottom py-2"
                            data-chart-status="{{ $status }}"
                            data-label="{{ __('checkout.order_status.'.$status) }}"
                            data-count="{{ $data['count'] }}"
                            data-percent="{{ $data['percent'] }}"
                        >
                            <button
                                type="button"
                                class="btn btn-sm btn-outline theme-inverse"
                                aria-label="{{ __('management.report_copy.chart_toggle', ['status' => __('checkout.order_status.'.$status)]) }}"
                                aria-pressed="true"
                                data-chart-toggle
                                hidden
                            >
                                <span class="admin-report-swatch d-inline-block" aria-hidden="true"></span>
                                {{ __('checkout.order_status.'.$status) }}
                            </button>
                            <a
                                href="{{ route('admin.orders.index', ['status' => $status]) }}"
                                class="fg-body ms-auto small"
                                aria-label="{{ __('checkout.order_status.'.$status) }}: {{ $data['count'] }} ({{ $data['percent'] }}%) — {{ __('management.report_copy.chart_orders') }}"
                                data-chart-link
                            >
                                <span data-chart-fallback>{{ __('checkout.order_status.'.$status) }}: </span>
                                <strong>{{ number_format($data['count']) }}</strong>
                                ({{ $data['percent'] }}%)
                                <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="small fg-3 mb-0">{{ __('management.report_copy.chart_navigation') }}</p>
            </div>
        </div>
    @endif
</section>
