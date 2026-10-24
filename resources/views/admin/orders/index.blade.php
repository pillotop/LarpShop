@extends('layouts.admin')

@section('title', 'Order Investigation (UC-44)')
@section('page_title', 'Orders & Disputes')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <header class="pb-3 border-bottom mb-4">
            <h1 class="h5 fw-bold text-dark mb-1"><i class="bi bi-receipt text-primary me-2"></i>Order Investigation</h1>
            <p class="text-muted small mb-0">Search every order on the marketplace and open one to review its items, vendors, and totals.</p>
        </header>

        {{-- Order Status Tabs and Search (UC-44) --}}
        <x-admin.order-filter-tabs :statuses="$statuses" :counts="$counts" :status="$status" :keyword="$keyword" />

        {{-- Orders Table Component (UC-44) --}}
        <x-admin.order-table :orders="$orders" />
    </div>
</div>
@endsection
