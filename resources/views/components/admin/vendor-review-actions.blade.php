@props(['vendor'])

<section class="border-top pt-4 mt-4">
    <h2 class="h5 fw-semibold">{{ __('onboarding.review.decision') }}</h2>
    <form
        action="{{ route('admin.vendors.reject', $vendor) }}"
        method="POST"
        class="mb-3"
    >
        @csrf
        <label for="rejection-reason" class="form-label">{{ __('onboarding.review.reason') }}</label>
        <textarea
            id="rejection-reason"
            name="rejection_reason"
            class="form-control mb-3"
            rows="3"
            maxlength="2000"
            required
        >{{ old('rejection_reason') }}</textarea>
        <button type="submit" class="btn btn-outline theme-inverse ">
            {{ __('management.reject') }}
        </button>
    </form>
    <form
        action="{{ route('admin.vendors.approve', $vendor) }}"
        method="POST"
    >
        @csrf
        <button type="submit" class="btn btn-solid theme-inverse ">
            {{ __('management.approve') }}
        </button>
    </form>
</section>
