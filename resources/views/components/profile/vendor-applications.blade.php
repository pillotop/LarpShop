@props(['applications'])

@if ($applications !== null && $applications->isNotEmpty())
    <section class="mb-4" aria-label="{{ __('onboarding.saved_applications') }}">
        <h3 class="h6 fw-semibold mb-3">{{ __('onboarding.saved_applications') }}</h3>
        <ul class="list-unstyled d-grid gap-2 mb-3">
            @foreach ($applications as $application)
                <li class="border rounded-2 p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-semibold">{{ $application->store_name ?: __('onboarding.untitled') }}</div>
                        <span class="small fg-3">{{ __('onboarding.status.'.$application->status->value) }}</span>
                    </div>
                    <a
                        href="{{ route('vendor-applications.show', $application) }}"
                        class="btn btn-outline theme-primary rounded-2"
                    >
                        {{ __('onboarding.resume') }}
                    </a>
                </li>
            @endforeach
        </ul>
        {{ $applications->withQueryString()->fragment('pane-vendor')->links() }}
    </section>
@endif
