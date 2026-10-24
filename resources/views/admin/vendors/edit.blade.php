@extends('layouts.admin')

@section('title', 'Cấu hình gian hàng Vendor (UC-29) | Admin')
@section('page_title', 'Cấu hình thông tin gian hàng')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
            <div>
                <h1 class="h5 fw-bold text-dark mb-1">
                    <i class="bi bi-gear text-primary me-2"></i>Cấu hình gian hàng: {{ $vendor->store_name }}
                </h1>
                <p class="text-muted small mb-0">Quản trị viên chỉnh sửa thông tin gian hàng và cấu hình trạng thái hoạt động (UC-29).</p>
            </div>
            <div>
                <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
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
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tên gian hàng <span class="text-danger">*</span></label>
                    <input type="text" name="store_name" class="form-control rounded-3" value="{{ old('store_name', $vendor->store_name) }}" required>
                    <div class="form-text">Tên thương hiệu hiển thị công khai trên sàn LarpShop.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Trạng thái hoạt động <span class="text-danger">*</span></label>
                    <select name="status" class="form-select rounded-3">
                        <option value="pending" @selected(old('status', $vendor->status) === 'pending')>Đang chờ duyệt (Pending)</option>
                        <option value="approved" @selected(old('status', $vendor->status) === 'approved')>Đã phê duyệt (Approved)</option>
                        <option value="active" @selected(old('status', $vendor->status) === 'active')>Đang hoạt động (Active)</option>
                        <option value="suspended" @selected(old('status', $vendor->status) === 'suspended')>Tạm ngưng hoạt động (Suspended)</option>
                        <option value="rejected" @selected(old('status', $vendor->status) === 'rejected')>Bị từ chối (Rejected)</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Mô tả gian hàng</label>
                    <textarea name="description" class="form-control rounded-3" rows="4" placeholder="Giới thiệu về gian hàng, chính sách bán hàng...">{{ old('description', $vendor->description) }}</textarea>
                </div>

                <div class="col-12 pt-3 border-top mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.vendors.index') }}" class="btn btn-light rounded-pill px-4">Hủy</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-save me-1"></i>Lưu cấu hình
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
