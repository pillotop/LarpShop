@extends('layouts.admin')

@section('title', __('management.store_configuration'))
@section('page_title', __('management.store_configuration'))

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-2 p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
            <div>
                <h1 class="h5 fw-bold fg-body mb-1">
                    <i class="bi bi-gear fg-primary me-2"></i>{{ __('management.configure_store', ['name' => $vendor->store_name]) }}
                </h1>
                <p class="fg-3 small mb-0">{{ __('onboarding.review.configuration_help') }}</p>
            </div>
            <div>
                <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline theme-secondary rounded-2 px-3">
                    <i class="bi bi-arrow-left me-1"></i>{{ __('management.back_to_list') }}
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert theme-danger alert-dismissible fade show rounded-2 mb-4" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('admin.vendors.update', $vendor) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="md:col-6">
                    <label class="form-label fw-semibold">{{ __('management.store_name') }} <span class="fg-danger">*</span></label>
                    <input type="text" name="store_name" class="form-control rounded-2" value="{{ old('store_name', $vendor->store_name) }}" required>
                    <div class="form-text">{{ __('management.public_brand_name_displayed_on_larpshop') }}</div>
                </div>

                <div class="md:col-6">
                    <p class="fw-semibold mb-1">{{ __('management.operating_status') }}</p>
                    <p>{{ __('products.vendor_status.'.$vendor->status) }}</p>
                    <p class="small fg-3">{{ __('onboarding.review.configuration_help') }}</p>
                    <a href="{{ route('admin.vendors.show', $vendor) }}">{{ __('onboarding.review.details') }}</a>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('management.store_description') }}</label>
                    <textarea name="description" class="form-control rounded-2" rows="4" placeholder="{{ __('management.introduce_your_store_and_sales_policies') }}">{{ old('description', $vendor->description) }}</textarea>
                </div>

                <div class="col-12 pt-3 border-top mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.vendors.index') }}" class="btn btn-solid theme-secondary rounded-2 px-4">{{ __('management.cancel') }}</a>
                    <button type="submit" class="btn btn-solid theme-primary rounded-2 px-4">
                        <i class="bi bi-save me-1"></i>{{ __('management.save_configuration') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
