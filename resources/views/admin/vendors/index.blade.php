@extends('layouts.admin')

@section('title', __('management.store_approval_and_management'))
@section('page_title', __('management.store_approval_and_management'))

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
            <div>
                <h1 class="h5 fw-bold fg-body mb-1">
                    <i class="bi bi-patch-check fg-primary me-2"></i>{{ __('management.store_approval_and_management') }}
                </h1>
                <p class="fg-3 small mb-0">{{ __('management.vendors_review_help') }}</p>
            </div>
        </div>

        {{-- Status Filter Tabs (Pills) Component --}}
        <x-admin.vendor-filter-tabs :counts="$counts" :status="$status" />

        {{-- Vendor Table Component --}}
        <x-admin.vendor-table :vendors="$vendors" />
    </div>
</div>
@endsection
