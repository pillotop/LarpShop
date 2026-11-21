<div class="card p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="h5 fw-bold mb-1">
                <i class="bi bi-graph-up-arrow fg-primary me-2" aria-hidden="true"></i>
                {{ __('management.platform_overview') }}
            </h1>
            <p class="fg-2 small mb-0">
                {{ __('management.report_copy.period_end', [
                    'time' => $reportPeriod->until->format('Y-m-d H:i'),
                    'timezone' => $reportPeriod->until->timezoneName,
                ]) }}
            </p>
            @if ($reportPeriod->since)
                <p class="fg-2 small mb-0">
                    {{ __('management.report_copy.period_start', ['time' => $reportPeriod->since->format('Y-m-d H:i')]) }}
                </p>
            @endif
        </div>
        <form
            method="GET"
            action="{{ route('admin.reports.index') }}"
            class="d-flex flex-wrap align-items-end gap-3"
        >
            <div>
                <label for="report-range" class="form-label d-block small">{{ __('management.report_copy.period') }}</label>
                <select
                    id="report-range"
                    name="range"
                    class="form-control"
                    onchange="this.form.requestSubmit()"
                >
                    @foreach ($ranges as $value)
                        <option value="{{ $value }}" @selected($range === $value)>
                            {{ __('management.periods.'.$value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-sales-source" class="form-label d-block small">{{ __('management.report_copy.sales_source') }}</label>
                <select
                    id="report-sales-source"
                    name="sales_source"
                    class="form-control"
                    onchange="this.form.requestSubmit()"
                >
                    @foreach ($salesSources as $source)
                        <option value="{{ $source }}" @selected($salesSource === $source)>
                            {{ __('management.report_copy.sources.'.$source) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <noscript>
                <button type="submit" class="btn btn-solid theme-inverse">{{ __('management.apply') }}</button>
            </noscript>
        </form>
    </div>
</div>
