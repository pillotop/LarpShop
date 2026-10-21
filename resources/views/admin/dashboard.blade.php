@extends('layouts.admin')

@section('title', 'Bảng điều khiển quản trị (Dashboard)')
@section('page_title', 'Bảng điều khiển quản trị')

@section('content')
<div class="container-fluid p-0">
    {{-- Metric Stat Cards (UC-38) Component --}}
    <x-admin.dashboard-metrics :metrics="$metrics" />

    {{-- Main Row: Pending Vendor Requests & Recent Active Vendors Components --}}
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <x-admin.pending-vendor-requests :pending-vendor-requests="$pendingVendorRequests" :metrics="$metrics" />
        </div>

        <div class="col-12 col-xl-4">
            <x-admin.recent-active-vendors :recent-vendors="$recentVendors" />
        </div>
    </div>
</div>
@endsection
