<section class="mt-4 mb-4" aria-labelledby="platform-operations-title">
    <h2 id="platform-operations-title" class="h5 fw-bold">{{ __('management.report_operations.title') }}</h2>
    <p class="small fg-2">{{ __('management.report_operations.scope') }}</p>
    <div class="row g-4">
        <div class="col-12 xl:col-6">
            @include('admin.reports.partials.inventory')
        </div>
        <div class="col-12 xl:col-6">
            @include('admin.reports.partials.fulfillment')
        </div>
        <div class="col-12 xl:col-6">
            @include('admin.reports.partials.applications')
        </div>
        <div class="col-12 xl:col-6">
            @include('admin.reports.partials.reviews')
        </div>
    </div>
</section>
