@extends('layouts.admin')

@section('title', __('management.display.order').''.$order->order_id.' (UC-44)')
@section('page_title', __('management.display.order').''.$order->order_id)

@section('content')
<div class="container-fluid p-0">
    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-solid theme-secondary rounded-pill mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('management.back_to_orders') }}
    </a>

    {{-- Order Summary, Totals & Status (UC-44) --}}
    <x-admin.order-summary-cards
        :order="$order"
        :computed-total="$computedTotal"
        :total-matches="$totalMatches"
        :can-cancel="$canCancel"
    />

    {{-- Vendor Breakdown and Line Items (UC-44) --}}
    <x-admin.order-vendor-breakdown :by-vendor="$byVendor" />
</div>
@endsection
