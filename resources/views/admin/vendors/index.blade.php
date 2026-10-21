@extends('layouts.admin')

@section('title', 'Phê duyệt & Quản lý đơn đăng ký Vendor (UC-40)')
@section('page_title', 'Quản lý & Xét duyệt hồ sơ Vendor')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
            <div>
                <h1 class="h5 fw-bold text-dark mb-1">
                    <i class="bi bi-patch-check text-primary me-2"></i>Xét duyệt và Quản lý gian hàng (UC-40)
                </h1>
                <p class="text-muted small mb-0">Xem xét hồ sơ đăng ký mở gian hàng từ khách hàng, thực hiện phê duyệt, từ chối hoặc tạm khóa gian hàng vi phạm.</p>
            </div>
        </div>

        {{-- Status Filter Tabs (Pills) Component --}}
        <x-admin.vendor-filter-tabs :counts="$counts" :status="$status" />

        {{-- Vendor Table Component --}}
        <x-admin.vendor-table :vendors="$vendors" />
    </div>
</div>
@endsection
