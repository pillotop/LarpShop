@extends('layouts.admin')

@section('title', __('onboarding.review.title'))
@section('page_title', __('onboarding.review.title'))

@section('content')
    <div class="border p-3 md:p-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold">{{ $vendor->store_name }}</h1>
                <p class="fg-3 mb-1">{{ $vendor->user?->username }} · {{ $vendor->user?->email }}</p>
                <p class="mb-0">{{ __('products.vendor_status.'.$vendor->status) }}</p>
            </div>
            <a href="{{ route('admin.vendors.index') }}" class="fg-body">
                {{ __('management.back_to_list') }}
            </a>
        </div>

        @if ($errors->any())
            <div class="border border p-3 mb-4" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($application)
            <x-admin.vendor-application-details :application="$application" :countries="$countries" />
        @else
            <p class="border p-3">{{ __('onboarding.review.legacy') }}</p>
            <p class="text-break">{{ $vendor->description ?: __('onboarding.client.empty') }}</p>
        @endif

        @if ($vendor->status === 'pending')
            <x-admin.vendor-review-actions :vendor="$vendor" />
        @endif
    </div>
@endsection
